import React, { useState, useEffect } from 'react';
import { Form, Button, Alert, Container } from 'react-bootstrap';
import { useSearchParams } from 'react-router-dom';

const PasswordResetTEST = () => {
  const [searchParams] = useSearchParams();
  const [login, setLogin] = useState('');
  const [key, setKey] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Recupera o login e a chave da URL
  useEffect(() => {
    const loginParam = searchParams.get('login');
    const keyParam = searchParams.get('key');

    if (loginParam && keyParam) {
      setLogin(loginParam);
      setKey(keyParam);
    }
  }, [searchParams]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (password !== confirmPassword) {
      setMessage('As senhas não coincidem.');
      setIsError(true);
      return;
    }

    try {
      const response = await fetch(
        'http://miraup.test/json/api/v1/password/reset',
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ login, password, key }),
        },
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Erro ao resetar a senha.');
      }

      setMessage('Senha resetada com sucesso.');
      setIsError(false);
    } catch (error) {
      setMessage(error.message || 'Erro ao resetar a senha.');
      setIsError(true);
    }
  };

  return (
    <Container className="mt-5">
      <h2>Resetar Senha</h2>
      {message && (
        <Alert variant={isError ? 'danger' : 'success'}>{message}</Alert>
      )}
      <Form onSubmit={handleSubmit}>
        <Form.Group className="mb-3">
          <Form.Label>Usuário</Form.Label>
          <Form.Control type="text" value={login} disabled />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>Chave de Recuperação</Form.Label>
          <Form.Control type="text" value={key} disabled />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>Nova Senha</Form.Label>
          <Form.Control
            type="password"
            placeholder="Digite a nova senha"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
          />
        </Form.Group>

        <Form.Group className="mb-3">
          <Form.Label>Confirme a Nova Senha</Form.Label>
          <Form.Control
            type="password"
            placeholder="Confirme a nova senha"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            required
          />
        </Form.Group>

        <Button variant="primary" type="submit">
          Resetar Senha
        </Button>
      </Form>
    </Container>
  );
};

export default PasswordResetTEST;
