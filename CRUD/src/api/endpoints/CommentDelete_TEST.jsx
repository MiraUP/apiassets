import React from 'react';
import { Button } from 'react-bootstrap';

const APIPageCommentDelete = ({ CommentID }) => {
  const token = localStorage.getItem('token') || '';
  const [comments, setComments] = React.useState('');
  const [message, setMessage] = React.useState('');

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/comment/${comments}`, {
      method: 'DELETE',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        setMessage(json.message);
        console.log(json.data);
      });
  }, [comments]);

  return (
    <Button
      variant="danger"
      className="w-50 h-100"
      onClick={() => setComments(CommentID)}
    >
      Excluir
    </Button>
  );
};

export default APIPageCommentDelete;
