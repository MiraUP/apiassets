import React from 'react';
import { Button, Col, Form, Row, Card } from 'react-bootstrap';

const MediaGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [mediaType, setMediaType] = React.useState('');
  const [typeGet, setTypeGet] = React.useState('all');
  const [parent, setParent] = React.useState('');
  const [mediaData, setMediaData] = React.useState([]);
  const [error, setError] = React.useState('');

  React.useEffect(() => {
    let URL;
    mediaType === 'user'
      ? (URL = 'http://miraup.test/json/api/users')
      : (URL = 'http://miraup.test/json/api/asset');

    fetch(URL, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        json.code === 'error' && setError(json.message);
        setTypeGet(json.data);
        return json.data;
      });

    fetch(
      `http://miraup.test/json/api/media?post-type=${mediaType}&parent=${parent}`,
      {
        method: 'GET',
        headers: {
          Authorization: 'Bearer ' + token,
        },
      },
    )
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        json.code === 'error' && setError(json.message);
        setMediaData(json.data);
        //console.log(json.data);
        return json.data;
      });
  }, [mediaType, parent]);

  function handleSubmit(event) {
    event.preventDefault();
    //   fetch(`http://miraup.test/json/api/media`, {
    //     method: 'GET',
    //     headers: {
    //       Authorization: 'Bearer ' + token,
    //     },
    //   })
    //     .then((response) => {
    //       return response.json();
    //     })
    //     .then((json) => {
    //       json.code === 'error' && setError(json.message);
    //       return json.data;
    //     });
  }
  return (
    <>
      <h2>MEDIA GET</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs={4}>
            <label>Type media</label>
            <Form.Select
              as="select"
              value={mediaType}
              onChange={({ target }) => setMediaType(target.value)}
            >
              <option value="all">Todas</option>
              <option value="user">User</option>
              <option value="asset">Assets</option>
              <option value="asset-icon">Asset Icon</option>
            </Form.Select>
          </Col>
          {mediaType === 'user' && (
            <Col xs={4}>
              <label>Escolha um usuário</label>
              <Form.Select
                as="select"
                value={parent}
                onChange={({ target }) => setParent(target.value)}
              >
                <option value="0">Todos</option>
                {typeGet &&
                  typeGet.length > 0 &&
                  typeGet.map(({ id, username }) => (
                    <option key={id} value={id}>
                      {username}
                    </option>
                  ))}
              </Form.Select>
            </Col>
          )}
          {mediaType != 'user' && (
            <Col xs={4}>
              <label>Escolha um Ativo</label>
              <Form.Select
                as="select"
                value={parent}
                onChange={({ target }) => setParent(target.value)}
              >
                <option value="0">Todos</option>
                {typeGet &&
                  typeGet.length > 0 &&
                  typeGet.map(({ id, title }) => (
                    <option key={id} value={id}>
                      {title}
                    </option>
                  ))}
              </Form.Select>
            </Col>
          )}
          <Row>
            {mediaType === 'user' &&
              mediaData &&
              mediaData.length > 0 &&
              mediaData.map(({ id_user, id_media, username, media }, index) => (
                <Col key={index}>
                  <Card>
                    <Card.Img variant="top" src={media} />
                    <Card.Body>
                      <Card.Title>
                        User - {id_user}. {username}
                      </Card.Title>
                      <Card.Text>Media {id_media}</Card.Text>
                    </Card.Body>
                  </Card>
                </Col>
              ))}
            {mediaType != 'user' &&
              mediaType != 'all' &&
              mediaData &&
              mediaData.length > 0 &&
              mediaData.map(({ id, title, category, thumbnail, previews }) => (
                <Col key={id}>
                  <Card>
                    <Card.Img
                      variant="top"
                      src={thumbnail}
                      style={{ maxWidth: '300px', maxHeight: '300px' }}
                    />
                    <Card.Body>
                      <Card.Title>Asset - {title}</Card.Title>
                      <Card.Text>
                        {previews &&
                          previews.map(({ id, url }) => (
                            <img
                              key={id}
                              src={url}
                              style={{
                                width: '250px',
                                height: '250px',
                                margin: '15px',
                              }}
                            />
                          ))}
                      </Card.Text>
                    </Card.Body>
                  </Card>
                </Col>
              ))}

            {mediaType === 'all' &&
              mediaData &&
              mediaData.length > 0 &&
              mediaData.map(({ id, title, metadata, media_url, mime_type }) => (
                <Col key={id}>
                  <Card>
                    <Card.Img
                      variant="top"
                      src={media_url}
                      style={{ width: '250px' }}
                    />
                    <Card.Body>
                      <Card.Title>
                        Media - {id} - {title}
                      </Card.Title>
                      <Card.Text>
                        <p>
                          Filesize: {metadata && metadata.filesize}
                          bytes
                        </p>
                        <p>
                          Dimensions: {metadata && metadata.width}px |{' '}
                          {metadata && metadata.height}px
                        </p>
                        <p>Mime type: {metadata && mime_type}</p>
                      </Card.Text>
                    </Card.Body>
                  </Card>
                </Col>
              ))}
          </Row>
        </Row>
        <Row>
          <Col>{error && <p>{error}</p>}</Col>
        </Row>
      </form>
    </>
  );
};

export default MediaGetTEST;
