import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Form,
  Card,
  Spinner,
  Figure,
} from 'react-bootstrap';

function MediaList() {
  const [postType, setPostType] = useState('');
  const [parentId, setParentId] = useState('');
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [getPostType, setGetPostType] = useState([]);

  const token = localStorage.getItem('token'); // Assume que o token está armazenado aqui

  React.useEffect(() => {
    let URL;
    postType === 'user'
      ? (URL = 'http://miraup.test/json/api/users')
      : (URL = 'http://miraup.test/json/api/asset?total=-1');

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
        setGetPostType(json.data);
        return json.data;
      });
  }, [postType]);

  const fetchMedia = async () => {
    if (!postType) return;
    setLoading(true);
    setError('');
    try {
      const response = await fetch(
        `http://miraup.test/json/api/media?post-type=${postType}&parent=${parentId}`,
        {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );
      if (!response.ok) throw new Error('Falha na requisição');
      const result = await response.json();
      setData(result.data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMedia();
  }, [parentId]);

  return (
    <Container>
      <Row className="mb-3">
        <Col>
          <Form.Group>
            <Form.Label>Tipo de Post</Form.Label>
            <Form.Control
              as="select"
              value={postType}
              onChange={(e) => setPostType(e.target.value)}
            >
              <option value="">Selecione...</option>
              <option value="user">User</option>
              <option value="asset">Asset</option>
            </Form.Control>
          </Form.Group>
        </Col>
        {postType === 'user' && (
          <Col>
            <Form.Group>
              <Form.Label>Usuário</Form.Label>
              <Form.Control
                as="select"
                value={parentId}
                onChange={(e) => setParentId(e.target.value)}
              >
                <option value="">Selecione...</option>
                {getPostType.length > 0 &&
                  getPostType.map((Type) => (
                    <option key={Type.id} value={Type.id}>
                      {Type.name}
                    </option>
                  ))}
              </Form.Control>
            </Form.Group>
          </Col>
        )}
        {postType != 'user' && (
          <Col>
            <Form.Group>
              <Form.Label>Ativo</Form.Label>
              <Form.Control
                as="select"
                value={parentId}
                onChange={(e) => setParentId(e.target.value)}
              >
                <option value="">Selecione...</option>
                {getPostType.length > 0 &&
                  getPostType.map((Type) => (
                    <option key={Type.id} value={Type.id}>
                      {Type.title}
                    </option>
                  ))}
              </Form.Control>
            </Form.Group>
          </Col>
        )}
      </Row>
      {loading && <Spinner animation="border" />}
      {error && <div className="alert alert-danger">{error}</div>}
      <Row>
        {console.log(data)}
        {data &&
          data.length > 0 &&
          data.map((item) => (
            <Col key={item.id} md={4} className="mb-3">
              <Card>
                <Card.Img variant="top" src={item.thumbnail || item.photo} />
                <Card.Body>
                  <Card.Title>{item.title}</Card.Title>
                </Card.Body>
              </Card>
            </Col>
          ))}
        {data.title && (
          <Col xs={6} className="mb-3">
            <Card>
              <Card.Img variant="top" src={data.thumbnail.url} />
              <Card.Body>
                <Card.Title>{data.title}</Card.Title>
                <Row>
                  {data.previews &&
                    data.previews != Array.isArray(data.previews) &&
                    data.previews.length > 0 &&
                    data.previews.map((preview) => (
                      <Col xs={4} key={preview.id}>
                        <Figure>
                          <Figure.Image
                            width={150}
                            alt={preview.title}
                            src={preview.url}
                            rounded
                          />
                          <Figure.Caption>
                            <p>
                              <b>Title:</b> {preview.title}
                            </p>
                            {preview.icon_category && (
                              <p>
                                <b>Categorias do Ícone:</b>{' '}
                                {preview.icon_category.map((category) => (
                                  <span key={category.term_id}>
                                    {' '}
                                    {category.name},{' '}
                                  </span>
                                ))}
                              </p>
                            )}
                            {preview.icon_styles && (
                              <p>
                                <b>Estilo do Ícone:</b>{' '}
                                {preview.icon_styles[0].name}
                              </p>
                            )}
                            {preview.icon_tag && (
                              <p>
                                <b>Tags do Ícone:</b>
                                {preview.icon_tag.map((icon_tag) => (
                                  <span key={icon_tag.term_id}>
                                    {' '}
                                    {icon_tag.name},{' '}
                                  </span>
                                ))}
                              </p>
                            )}{' '}
                            {preview.mime_type && (
                              <p>
                                <b>Mime Type:</b> {preview.mime_type}
                              </p>
                            )}{' '}
                            {/**/}
                          </Figure.Caption>
                        </Figure>
                      </Col>
                    ))}
                </Row>
              </Card.Body>
            </Card>
          </Col>
        )}
      </Row>
    </Container>
  );
}

export default MediaList;
