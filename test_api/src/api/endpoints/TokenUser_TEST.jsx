import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const TokenPostTEST = () => {
  const [username, setUsername] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [token, setToken] = React.useState('');
  const [error, setError] = React.useState('');

  function handleSubmit(event) {
    event.preventDefault();
    setError('');
    fetch('http://miraup.test/json/jwt-auth/v1/token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        username,
        password,
      }),
    })
      .then((response) => {
        console.log(response);
        return response.json();
      })
      .then((json) => {
        console.log(json);
        setToken(json.token);
        localStorage.setItem('token', json.token);
        json.code === '[jwt_auth] incorrect_password' && setError(json.message);
        return json;
      });
  }

  return (
    <>
      <h2>TOKEN USER POST</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Username"
              autoComplete="username"
              value={username}
              onChange={({ target }) => setUsername(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="password"
              placeholder="Password"
              autoComplete="current-password"
              value={password}
              onChange={({ target }) => setPassword(target.value)}
            />
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="w-100">
              entrar
            </Button>
          </Col>
        </Row>
        <Row>
          <Col>
            <p className="text-break">
              {token}
              {error}
            </p>
          </Col>
        </Row>
      </form>
    </>
  );
};

export default TokenPostTEST;
