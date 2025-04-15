import React from 'react';
import { Button, Col, Form, Row, Card, Badge } from 'react-bootstrap';

const NotificationErrorPost = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [assets, setAssets] = React.useState([]);
  const [page, setPage] = React.useState('');
  const [notificationType, setNotificationType] = React.useState('');
  const [title, setTitle] = React.useState('');
  const [message, setMessage] = React.useState('');
  const [details, setDetails] = React.useState('');
  const [error, setError] = React.useState('');

  React.useEffect(() => {
    fetch('http://miraup.test/json/api/asset?total=-1', {
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
  }, [token]);

  const handleSubmit = (event) => {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/notification-error/`, {
      method: 'POST',
      headers: {
        Authorization: 'Bearer ' + token,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        error_type: notificationType,
        page,
        title,
        page,
        message,
        details,
      }),
    })
      .then((response) => response.json())
      .then((json) => {
        console.log(json.data);
        if (!json.success) {
          setError(json.message);
        }
      });
  };

  return (
    <>
      <h2>NOTIFICATIONS ERROR</h2>
      <form onSubmit={handleSubmit}>
        <Row>
          <Col xs={4}>
            <Form.Group controlId="formBasicEmail">
              <Form.Label>Tipo de erro:</Form.Label>
              <Form.Select
                value={notificationType}
                onChange={(e) => setNotificationType(e.target.value)}
              >
                <option value="" disabled>
                  Selecione o tipo de erro
                </option>
                <option value="Login">Problema com login</option>
                <option value="Cadastro">Problema com cadastro</option>
                <option value="Senha">Problema com recuperar senha</option>
                <option value="Permissão">
                  Problema com permissão ao sistema
                </option>
                <option value="Outros">Outros</option>
              </Form.Select>
            </Form.Group>
          </Col>
          <Col xs={4}>
            <Form.Group controlId="formBasicEmail">
              <Form.Label>Página ou Asset</Form.Label>
              <Form.Select
                value={page}
                onChange={(e) => setPage(e.target.value)}
              >
                <option value="" disabled>
                  Informe em que página encontrou o erro
                </option>
                <option value="user">Usuarios</option>
                <option value="password">Senha</option>
                <option value="assets">Ativos</option>
                <option value="comments">comentários</option>
                <option value="taxonomy">Taxonomias</option>
                <option value="media">Mídias</option>
                <option value="notification">Notificações</option>
                {assets.length > 0 &&
                  assets.map((asset) => (
                    <option key={asset.id} value={asset.id}>
                      {asset.title}
                    </option>
                  ))}
              </Form.Select>
            </Form.Group>
          </Col>
          <Col xs={4}>
            <Form.Label>Titulo</Form.Label>
            <Form.Control
              type="text"
              placeholder="De um titulo para o erro"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          </Col>
        </Row>
        <Row className="mt-3">
          <Col xs={12}>
            <Form.Label>Mensagem principal</Form.Label>
            <Form.Control
              type="text"
              placeholder="Descreva o que aconteceu"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
            />
          </Col>
          <Col xs={12} className="mt-3">
            <Form.Label>Detalhe do erro</Form.Label>
            <Form.Control
              type="text"
              placeholder="Detalhe do erro"
              value={details}
              onChange={(e) => setDetails(e.target.value)}
            />
          </Col>
        </Row>
        <Row className="mt-3 mb-3">
          <Col xs={12}>
            <Button variant="primary" type="submit" className="w-100">
              Enviar
            </Button>
          </Col>
        </Row>
      </form>
    </>
  );
};

export default NotificationErrorPost;
