import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const AssetsCommentTEST = ({ assetId }) => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [comments, setComments] = React.useState('');
  const [newComments, setNewComments] = React.useState('');

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/comment/${assetId}`, {
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
        setComments(json.data);
        return json.data;
      });
  }, []);
  console.log(comments);

  const handleSubmit = (event) => {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/comment/${assetId}`, {
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
        console.log(json);
        return json;
      })
      .catch((error) => {
        console.error('Erro:', error);
      });
  };

  return (
    <Row className="flex-column">
      <Col>
        <h4>Lista de comentários {console.log(comments)}</h4>
        {comments.length < 1 && <p>Nenhum comentário</p>}
        <ul>
          {comments &&
            comments.length > 0 &&
            comments != Array.isArray(comments) &&
            comments.map(({ id, author, content, date }) => (
              <li key={id}>
                <p>Autor: {author}</p>
                <p>
                  <small>Data: {date}</small>
                </p>
                <p>{content}</p>
              </li>
            ))}
        </ul>
      </Col>
      <Col>
        <form onSubmit={handleSubmit}>
          <Form.Control
            as="textarea"
            placeholder="New Comments"
            value={newComments}
            onChange={({ target }) => setNewComments(target.value)}
          />
          <Button type="submit" className="w-100">
            Comentar
          </Button>
        </form>
      </Col>
    </Row>
  );
};

export default AssetsCommentTEST;
