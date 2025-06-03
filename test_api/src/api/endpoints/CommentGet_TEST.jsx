import React from 'react';
import { Button, Col, Row, Card } from 'react-bootstrap';
import APIPageCommentPut from './CommentPut_TEST';
import APIPageCommentDelete from './CommentDelete_TEST';

const APIPageCommentGet = ({ PostID }) => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [comments, setComments] = React.useState([]);
  const [commentPut, setCommentPut] = React.useState('');
  const [commentPutStatus, setCommentPutStatus] = React.useState(false);

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/comment/${PostID}`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setComments(json.data);
        return json.data;
      });
  }, []);
  return (
    <Row>
      {comments &&
        comments != Array.isArray(comments) &&
        comments.length > 0 &&
        comments.map((comment) => (
          <Col key={comment.id} xs={12}>
            <Card>
              <Card.Body>
                <Card.Title>
                  <Row>
                    <Col xs={10}>
                      {comment.author} <br />
                      <small>
                        <small>{comment.date}</small>
                      </small>
                    </Col>
                    <Col xs={2}>
                      <Button
                        className="w-50 h-100"
                        onClick={() =>
                          setCommentPutStatus(!commentPutStatus) +
                          setCommentPut(comment.id)
                        }
                      >
                        {commentPutStatus === false
                          ? 'Editar'
                          : commentPut === comment.id
                          ? 'Cancelar'
                          : 'Editar'}
                      </Button>
                      <APIPageCommentDelete CommentID={comment.id} />
                    </Col>
                  </Row>
                </Card.Title>
                <hr />
                <Card.Text>
                  {commentPutStatus === false ? (
                    comment.content
                  ) : commentPut === comment.id ? (
                    <APIPageCommentPut PostID={PostID} CommentId={comment.id} />
                  ) : (
                    comment.content
                  )}
                </Card.Text>
              </Card.Body>
            </Card>
            <br />
          </Col>
        ))}
    </Row>
  );
};

export default APIPageCommentGet;
