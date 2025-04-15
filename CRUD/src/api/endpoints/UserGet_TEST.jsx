import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const UserGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [userid, setUserid] = React.useState([]);
  const [userquery, setUserquery] = React.useState(false);

  function handleSubmit(event) {
    event.preventDefault();

    let URL;
    userquery === false
      ? (URL = 'http://miraup.test/json/api/user')
      : (URL = `http://miraup.test/json/api/users/?id-user=${userid}`);

    fetch(URL, {
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
        console.log(json);
        json.code === 'error' && setError(json.message);
        setUserid(json.data);
        return json;
      });
  }
  return (
    <>
      <h2>USER GET</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs={4}>
            <Form.Check
              type="switch"
              id="users-query"
              label="Buscar todos os users"
              value={userquery}
              onChange={({ target }) => setUserquery(!userquery)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Token"
              value={token}
              onChange={({ target }) => setToken(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="ID user"
              //value={userid && userid[0].id}
              onChange={({ target }) => setUserid(target.value)}
            />
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="w-100">
              Buscar
            </Button>
            {userid.name}
          </Col>
        </Row>
      </form>
      {JSON.stringify(userid)}
    </>
  );
};

export default UserGetTEST;
