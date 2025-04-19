import React from 'react';
import { Button, Col, Form, Row, Card, Alert, Badge } from 'react-bootstrap';

const NotificationPutTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [listNotifications, setListNotifications] = React.useState([]);
  const [notificationSelect, setNotificationSelect] = React.useState('');
  const [title, setTitle] = React.useState('');
  const [reader, setReader] = React.useState('');
  const [content, setContent] = React.useState('');
  const [category, setCategory] = React.useState('');
  const [message, setMessage] = React.useState('');
  const [postID, setPostID] = React.useState('');
  const [urlPost, setUrlPost] = React.useState('');
  const [sender, setSender] = React.useState([]);
  const [marker, setMarker] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [assets, setAssets] = React.useState([]);
  const [user, setUser] = React.useState([]);
  const [error, setError] = React.useState('');

  React.useEffect(() => {
    setListNotifications([]);
    const url = notificationSelect
      ? `http://miraup.test/json/api/v1/notifications/${notificationSelect}/`
      : `http://miraup.test/json/api/v1/notifications/`;

    fetch(url, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        console.log(response);
        return response.json();
      })
      .then((json) => {
        if (json.success && Array.isArray(json.data)) {
          setListNotifications(json.data);
        }
        if (!Array.isArray(json.data)) {
          setTitle(json.data.title);
          setReader(json.data.read);
          setContent(json.data.content);
          setCategory(json.data.category);
          setMessage(json.data.message);
          setPostID(json.data.post_id);
          setUrlPost(json.data.url_post);
          setMarker(json.data.marker);
          setSender(json.recipients.senders.map((item) => item.user_id));
        }

        return json;
      });

    fetch(`http://miraup.test/json/api/v1/taxonomy?taxonomy=notification`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        if (json.success) {
          setTaxonomyList(json.data);
        }
      })
      .catch((error) => {});

    fetch('http://miraup.test/json/api/v1/asset?total=-1', {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        setAssets(json.data);
      })
      .catch((error) => console.error('Erro:', error));

    fetch('http://miraup.test/json/api/v1/users', {
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
        setUser(json.data);
        return json;
      });
  }, [token, notificationSelect]);

  const handleSubmit = (event) => {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/v1/notifications/`, {
      method: 'PUT',
      headers: {
        Authorization: 'Bearer ' + token,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: notificationSelect,
        title,
        change_read: reader,
        content,
        category,
        message,
        post_id: postID,
        marker,
        sender: sender.join(','),
      }),
    })
      .then((response) => response.json())
      .then((json) => {
        console.log(json.data);
        if (json.success) {
          setNotificationSelect('');
          setTitle('');
          setReader(false);
          setContent('');
          setCategory('');
          setMessage('');
          setPostID('');
          setMarker('');
          setSender([]);
        } else {
          setError(json.message);
        }
      });
  };

  const handleSender = (e) => {
    const updatedOptions = [...e.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);
    setSender(updatedOptions);
  };
  return (
    <>
      <h2>NOTIFICATIONS PUT</h2>
      <br />
      <form onSubmit={handleSubmit}>
        <Row>
          {!notificationSelect &&
            listNotifications.length > 0 &&
            listNotifications.map((notification) => (
              <Col
                xs={2}
                key={notification.id}
                onClick={() => setNotificationSelect(notification.id)}
                style={{ cursor: 'pointer' }}
              >
                <Card key={notification.id} className="position-relative mb-3">
                  {notification.read === false && (
                    <Badge
                      pill
                      bg="success"
                      className="position-absolute top-0 start-100 translate-middle p-2 border border-dark rounded-circle"
                    >
                      {' '}
                    </Badge>
                  )}
                  <Card.Body>
                    <Card.Title>{notification.title} </Card.Title>
                    <Card.Text>{notification.message}</Card.Text>
                    <Button variant="primary" type="submit">
                      Update Notification
                    </Button>
                  </Card.Body>
                </Card>
              </Col>
            ))}

          {notificationSelect && (
            <Col xs={12}>
              <Card className="position-relative mb-3">
                <Card.Body>
                  <Card.Title className="d-flex justify-content-between gap-4">
                    <Button onClick={() => setNotificationSelect('')}>
                      Voltar
                    </Button>{' '}
                    <label className="d-inline-flex w-100 align-items-center gap-2">
                      Título
                      <Form.Control
                        type="text"
                        placeholder="Título"
                        value={title}
                        onChange={(e) => setTitle(e.target.value)}
                      />
                    </label>
                    <label>
                      <small>Marcar como {reader ? 'não' : ''} lido</small>
                    </label>
                    <Form.Check
                      type="switch"
                      checked={reader} // Usa o estado individual de leitura
                      onChange={(e) => setReader(e.target.checked)}
                    />
                  </Card.Title>
                  <Card.Text>
                    <Row className="gap-2">
                      <Col xs={12}>
                        <label className="d-inline-flex w-100 align-items-center gap-2">
                          Mensagem curta
                          <Form.Control
                            type="text"
                            placeholder="Mensagem curta"
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                          />
                        </label>
                      </Col>
                      <Col xs={6} className="d-flex gap-2 flex-column">
                        <Col xs={12}>
                          <Form.Select
                            value={category && category[0].name}
                            onChange={(e) => setCategory(e.target.value)}
                          >
                            <option value="">Selecione uma categoria</option>
                            {taxonomyList.length > 0 &&
                              taxonomyList.map((item) => (
                                <option key={item.id} value={item.name}>
                                  {item.name}
                                </option>
                              ))}
                          </Form.Select>
                        </Col>
                        <Col xs={12}>
                          <Form.Select
                            value={marker}
                            onChange={(e) => setMarker(e.target.value)}
                          >
                            <option value="">Selecione um Marker</option>
                            <option value="flagged">Sinalizado</option>
                            {taxonomyList.length > 0 &&
                              taxonomyList.map((item) => (
                                <option
                                  key={item.id}
                                  value={item.name}
                                  style={{ textTransform: 'capitalize' }}
                                >
                                  {item.name}
                                </option>
                              ))}
                            <option value="delete">Excluir</option>
                          </Form.Select>
                        </Col>
                        <Col xs={12}>
                          <Form.Select
                            value={postID}
                            onChange={(e) => setPostID(e.target.value)}
                          >
                            <option value="">Selecione um Ativo</option>
                            {assets.length > 0 &&
                              assets.map((item) => (
                                <option key={item.id} value={item.id}>
                                  {item.title}
                                </option>
                              ))}
                          </Form.Select>
                        </Col>
                      </Col>
                      <Col>
                        <Form.Select
                          value={
                            sender.length > 0 && sender.map((sender) => sender)
                          }
                          onChange={handleSender}
                          multiple={true}
                        >
                          <option value="" disabled>
                            Selecione os remetentes
                          </option>
                          {user.length > 0 &&
                            user.map((item) => (
                              <option key={item.id} value={item.id}>
                                {item.name}
                              </option>
                            ))}
                        </Form.Select>
                      </Col>
                      <Col xs={12}>
                        <Form.Control
                          as="textarea"
                          rows={3}
                          placeholder="Conteúdo"
                          value={content}
                          onChange={(e) => setContent(e.target.value)}
                        />
                      </Col>
                    </Row>
                  </Card.Text>
                  <Button variant="primary" type="submit">
                    Atualizar Notificação
                  </Button>
                </Card.Body>
              </Card>
            </Col>
          )}
        </Row>
      </form>
    </>
  );
};

export default NotificationPutTEST;
