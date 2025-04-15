import React from 'react';
import { Button, Col, Form, Row, Card, Alert } from 'react-bootstrap';

const NotificationPostTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [category, setCategory] = React.useState('');
  const [userId, setUserId] = React.useState('');
  const [content, setContent] = React.useState('');
  const [userList, setUserList] = React.useState([]);
  const [message, setMessage] = React.useState({ type: '', text: '' });
  const [title, setTitle] = React.useState('');
  const [subtitle, setSubtitle] = React.useState('');

  React.useEffect(() => {
    fetch('http://miraup.test/json/api/users', {
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
        console.log(json.data);
        json.code === 'error' &&
          setMessage({ type: 'danger', text: json.code });
        json.code != 'error' &&
          setMessage({ type: 'success', text: json.message });
        setUserList(json.data);
        return json;
      });
  }, []);

  const handleSubmit = (event) => {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/notifications`, {
      method: 'POST',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        user_id: userId,
        category,
        title,
        message: subtitle,
        content,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => {
        setMessage({ type: 'danger', text: json.message });
        console.log(json.message);
        return json;
      })
      .catch((error) => {
        console.error('Erro:', error);
      });
  };

  return (
    <>
      <h2>NOTIFICATIONS POST</h2>
      <br />
      <form onSubmit={handleSubmit}>
        <Row>
          <Col>
            <Form.Control
              type="text"
              placeholder="Título"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          </Col>
          <Col>
            <Form.Control
              type="text"
              placeholder="Subtítulo"
              value={subtitle}
              onChange={(e) => setSubtitle(e.target.value)}
            />
          </Col>
        </Row>
        <br />
        <Row>
          <Col xs={6}>
            <Form.Select
              value={category}
              onChange={(e) => setCategory(e.target.value)}
            >
              <option value="">Selecione uma categoria</option>
              <option value="asset">Ativos</option>
              <option value="personal">Pessoal</option>
              <option value="system">Sistema</option>
            </Form.Select>
          </Col>
          <Col xs={6}>
            <Form.Select
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
            >
              <option>Selecione um usuário</option>
              {userList &&
                userList.length > 0 &&
                userList.map((user) => (
                  <option key={user.id} value={user.id}>
                    {user.name}
                  </option>
                ))}
            </Form.Select>
          </Col>
        </Row>
        <br />
        <Row>
          <Col>
            <Card>
              <Card.Body>
                <Card.Text>
                  <Form.Label>Mensagem de Notificação</Form.Label>
                  <Form.Control
                    as="textarea"
                    rows={3}
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                  />
                </Card.Text>
                <Button
                  type="submit"
                  variant="primary"
                  className="w-100"
                  size="lg"
                >
                  Disparar notificação
                </Button>
              </Card.Body>
            </Card>
            <br />
            {message.text && (
              <Alert variant={message.type} className="mt-3" dismissible>
                {message.text}
              </Alert>
            )}
          </Col>
        </Row>
      </form>
    </>
  );
};

export default NotificationPostTEST;
