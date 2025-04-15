import React from 'react';
import { Button, Form } from 'react-bootstrap';

const APIPageCommentPut = ({ PostID, CommentId }) => {
  const [token] = React.useState(localStorage.getItem('token') || '');
  const [comments, setComments] = React.useState([]);
  const [editedComment, setEditedComment] = React.useState('');
  const [isLoading, setIsLoading] = React.useState(false);

  // Carrega os comentários do post
  React.useEffect(() => {
    const fetchComments = async () => {
      try {
        const response = await fetch(
          `http://miraup.test/json/api/comment/${PostID}`,
          {
            method: 'GET',
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );

        if (!response.ok) {
          throw new Error('Falha ao carregar comentários');
        }

        const data = await response.json();
        setComments(data.data || []);

        // Encontra e define o comentário a ser editado
        const commentToEdit = data.data.find((c) => c.id === CommentId);
        if (commentToEdit) {
          setEditedComment(commentToEdit.content);
        }
      } catch (error) {
        console.error('Erro:', error);
      }
    };

    fetchComments();
  }, [PostID, CommentId, token]);

  // Atualiza o comentário
  const handleSubmit = async (event) => {
    event.preventDefault();
    setIsLoading(true);

    console.log(CommentId);

    try {
      const response = await fetch(
        `http://miraup.test/json/api/comment/${CommentId}`,
        {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            content: editedComment,
          }),
        },
      );
      console.log('response: ', response);

      if (!response.ok) {
        throw new Error('Falha ao atualizar comentário');
      }

      const json = await response.json();
      console.log('Sucesso: ', json.message);
      console.log('Sucesso: ', json.data);

      // Atualiza a lista de comentários localmente
      setComments(
        comments.map((c) =>
          c.id === CommentId ? { ...c, content: editedComment } : c,
        ),
      );
    } catch (error) {
      console.error('Erro:', error);
    } finally {
      setIsLoading(false);
    }
  };

  // Encontra o comentário específico para edição
  const commentToEdit = comments.find((c) => c.id === CommentId);

  if (!commentToEdit) {
    return <div>Comentário não encontrado</div>;
  }

  return (
    <Form onSubmit={handleSubmit}>
      <Form.Group controlId="commentEdit">
        <Form.Control
          as="textarea"
          rows={3}
          value={editedComment}
          onChange={(e) => setEditedComment(e.target.value)}
          placeholder="Editar comentário"
        />
      </Form.Group>
      <Button
        variant="primary"
        type="submit"
        disabled={isLoading}
        className="mt-2"
      >
        {isLoading ? 'Salvando...' : 'Salvar Alterações'}
      </Button>
    </Form>
  );
};

export default APIPageCommentPut;
