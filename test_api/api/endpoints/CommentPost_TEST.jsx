import React from 'react';
import { Button, Col, Row, Card, Form, Alert } from 'react-bootstrap';

const APIPageCommentPost = ({ PostID, newComment }) => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [newComments, setNewComments] = React.useState('');
  const [message, setMessage] = React.useState('');

  const handleSubmit = (event) => {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/v1/comment/${PostID}`, {
      method: 'POST',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        comment: newComments,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => {
        setMessage(json.message);
        return json;
      })
      .catch((error) => {
        console.error('Erro:', error);
      });
  };

  return (
    <form onSubmit={handleSubmit}>
      <Row>
        <Col xs={10}>
          <Form.Control
            as="textarea"
            placeholder="Novo comentário"
            value={newComments}
            onChange={({ target }) => setNewComments(target.value)}
          />
        </Col>
        <Col xs={2}>
          <Button type="submit" className="w-100 h-100">
            Comentar
          </Button>
        </Col>
        <Col>
          <br />
          {message !== '' && (
            <Alert variant="success" onClose={() => setMessage('')} dismissible>
              {message}
            </Alert>
          )}
        </Col>
      </Row>
    </form>
  );
};

export default APIPageCommentPost;
