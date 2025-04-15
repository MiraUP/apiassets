import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const UserPostTEST = () => {
  const [name, setName] = React.useState('');
  const [username, setUsername] = React.useState('');
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [role, setRole] = React.useState('');
  const [statusaccount, setStatusaccount] = React.useState('');
  const [photo, setPhoto] = React.useState('');
  const [error, setError] = React.useState('');

  function handleSubmit(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('displayname', name);
    formData.append('username', username);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('role', role);
    formData.append('statusaccount', statusaccount);
    formData.append('photo', photo);

    fetch('http://miraup.test/json/api/user', {
      method: 'POST',
      body: formData,
    })
      .then((response) => {
        console.log(response);
        return response.json();
      })
      .then((json) => {
        console.log(json);
        json.code === 'error' && setError(json.message);
        return json;
      });
  }
  console.log(photo);

  return (
    <>
      <h2>USER POST</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs="4">
            <label>
              Foto
              <Form.Control
                type="file"
                onChange={({ target }) => setPhoto(target.files[0])}
              />
            </label>
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Name"
              autoComplete="name"
              value={name}
              onChange={({ target }) => setName(target.value)}
            />
          </Col>
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
              type="email"
              placeholder="Email"
              autoComplete="email"
              value={email}
              onChange={({ target }) => setEmail(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="password"
              placeholder="Password"
              autoComplete="new-password"
              value={password}
              onChange={({ target }) => setPassword(target.value)}
            />
          </Col>
          <Col xs={4}>
            <label>
              Role
              <Form.Select
                value={role}
                onChange={({ target }) => setRole(target.value)}
              >
                s<option value="administrator">Administrator</option>
                <option value="editor">Editor</option>
                <option value="author">Author</option>
                <option value="contributor">Contributor</option>
                <option value="subscriber">Subscriber</option>
              </Form.Select>
            </label>
          </Col>
          <Col xs={4}>
            <label>
              Status Account
              <Form.Select
                value={statusaccount}
                onChange={({ target }) => setStatusaccount(target.value)}
              >
                s<option value="disabled">Disabled</option>
                <option value="activated">Activated</option>
                <option value="pending">Pending</option>
              </Form.Select>
            </label>
          </Col>
          <Col xs={4}>
            <Button type="submit" className="w-100">
              Cadastrar
            </Button>
          </Col>
        </Row>
        <Row>
          <Col>
            <p>{error}</p>
          </Col>
        </Row>
      </form>
    </>
  );
};

export default UserPostTEST;
