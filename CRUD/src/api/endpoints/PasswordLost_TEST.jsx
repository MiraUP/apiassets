import React, { useState } from 'react';
import { Form, Button, Alert, Container } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';

const PasswordLostTEST = () => {
  const [login, setLogin] = useState('');
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await fetch(
        'http://miraup.test/json/api/password/lost',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ login }),
        },
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erro ao enviar solicitação.');
      }

      setMessage('Email de recuperação enviado com sucesso.');
      setIsError(false);
      navigate('/password-reset'); // Redireciona para a tela de reset de senha
    } catch (error) {
      setMessage(error.message || 'Erro ao enviar solicitação.');
      setIsError(true);
    }
  };

  return (
    <Container className="mt-5">
      <h2>Recuperar Senha</h2>
      {message && (
        <Alert variant={isError ? 'danger' : 'success'}>{message}</Alert>
      )}
      <Form onSubmit={handleSubmit}>
        <Form.Group className="mb-3">
          <Form.Label>Usuário ou Email</Form.Label>
          <Form.Control
            type="text"
            placeholder="Digite seu usuário ou email"
            value={login}
            onChange={(e) => setLogin(e.target.value)}
            required
          />
        </Form.Group>
        <Button variant="primary" type="submit">
          Enviar
        </Button>
      </Form>
    </Container>
  );
};

export default PasswordLostTEST;
