import React, { useState, useEffect } from 'react';
import { Form, Button, Alert, Container } from 'react-bootstrap';

const UpdateUserForm = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [confirmationCode, setConfirmationCode] = useState('');
  const [showConfirmationCodeField, setShowConfirmationCodeField] =
    useState(false);
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Busca o token no localStorage
  const token = localStorage.getItem('token');

  // Verifica se o código de segurança já foi gerado
  useEffect(() => {
    const fetchUserData = async () => {
      try {
        const response = await fetch('http://miraup.test/json/api/user', {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          throw new Error('Erro ao buscar dados do usuário.');
        }

        const data = await response.json();
        console.log(data.data);
        // Verifica se o código de segurança (key_delete) já foi gerado
        if (data.data.key_delete) {
          setShowConfirmationCodeField(true);
        }
      } catch (error) {
        console.error('Erro ao buscar dados do usuário:', error);
        setMessage('Erro ao buscar dados do usuário.');
        setIsError(true);
      }
    };

    if (token) {
      fetchUserData();
    }
  }, [token]);

  // Função para enviar o formulário
  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await fetch('http://miraup.test/json/api/user', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          username,
          password,
          code: confirmationCode,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erro ao atualizar usuário.');
      }

      // Exibe mensagem de sucesso
      setMessage('Usuário atualizado com sucesso!');
      setIsError(false);
    } catch (error) {
      console.error('Erro ao atualizar usuário:', error);
      setMessage(error.message || 'Erro ao atualizar usuário.');
      setIsError(true);
    }
  };

  return (
    <Container className="mt-5">
      <h2>Deletar Usuário</h2>
      {message && (
        <Alert variant={isError ? 'danger' : 'success'}>{message}</Alert>
      )}
      <Form onSubmit={handleSubmit}>
        <Form.Group className="mb-3">
          <Form.Label>Usuário</Form.Label>
          <Form.Control
            type="text"
            placeholder="Digite seu nome de usuário"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            required
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>Senha</Form.Label>
          <Form.Control
            type="password"
            placeholder="Digite sua senha"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </Form.Group>

        {showConfirmationCodeField && (
          <Form.Group className="mb-3">
            <Form.Label>Código de Confirmação</Form.Label>
            <Form.Control
              type="text"
              placeholder="Digite o código de confirmação"
              value={confirmationCode}
              onChange={(e) => setConfirmationCode(e.target.value)}
              required
            />
          </Form.Group>
        )}

        <Button variant="danger" type="submit">
          Deletar Usuário
        </Button>
      </Form>
    </Container>
  );
};

export default UpdateUserForm;
