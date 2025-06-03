import React, { useEffect, useState } from 'react';
import { Button, Form, Row, Col, Alert } from 'react-bootstrap';

const UserUpdateForm = () => {
  const [userData, setUserData] = useState({
    id: '',
    username: '',
    name: '',
    email: '',
    password: '',
    password_new: '',
    password_confirm: '',
    status_account: '',
    code_email: '',
    roles: '',
    photo: '',
    goal: '',
    appearance_system: '',
    notification_asset: '',
    notification_email: '',
    notification_personal: '',
    notification_system: '',
    email_confirm: '',
  });
  const [photo, setPhoto] = React.useState('');
  const [message, setMessage] = useState({ type: '', text: '' });

  // Busca os dados do usuário logado
  useEffect(() => {
    const token = localStorage.getItem('token');
    if (!token) {
      setMessage({
        type: 'danger',
        text: 'Token não encontrado. Faça login novamente.',
      });
      return;
    }

    fetch('http://miraup.test/json/api/v1/user', {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          setUserData({
            ...data.data,
            password: '', // Não preenche a senha por segurança
            code_email: '', // Código de confirmação deve ser inserido manualmente
          });
        } else {
          setMessage({
            type: 'danger',
            text: 'Erro ao buscar dados do usuário.',
          });
        }
      })
      .catch((error) => {
        setMessage({
          type: 'danger',
          text: 'Erro na requisição: ' + error.message,
        });
      });
  }, []);

  // Atualiza os dados do usuário
  const handleSubmit = (event) => {
    event.preventDefault();

    const token = localStorage.getItem('token');
    if (!token) {
      setMessage({
        type: 'danger',
        text: 'Token não encontrado. Faça login novamente.',
      });
      return;
    }

    fetch('http://miraup.test/json/api/v1/user', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        id: userData.id,
        email: userData.email,
        password: userData.password,
        password_new: userData.password_new,
        password_confirm: userData.password_confirm,
        code_email: userData.code_email,
        name: userData.name,
        role: userData.roles[0],
        goal: userData.goal,
        appearance_system: userData.appearance_system,
        notification_asset: userData.notification_asset,
        notification_email: userData.notification_email,
        notification_personal: userData.notification_personal,
        notification_system: userData.notification_system,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          setMessage({
            type: 'success',
            text: 'Dados do usuário atualizados com sucesso!',
          });
        } else {
          setMessage({
            type: 'danger',
            text: data.message || 'Erro ao atualizar os dados.',
          });
        }
      })
      .catch((error) => {
        setMessage({
          type: 'danger',
          text: 'Erro na requisição: ' + error.message,
        });
      });

    const formData = new FormData(); //Necessário para fazer o Fecth com imagem.
    formData.append('photo', photo);
    if (photo) {
      fetch('http://miraup.test/json/api/v1/user/photo', {
        method: 'POST',
        headers: {
          Authorization: 'Bearer ' + token,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            setMessage({
              type: 'success',
              text: 'Dados do usuário atualizados com sucesso!',
            });
          } else {
            setMessage({
              type: 'danger',
              text: data.message || 'Erro ao atualizar a foto.',
            });
          }
        })
        .catch((error) => {
          setMessage({
            type: 'danger',
            text: 'Erro na requisição: ' + error.message,
          });
        });
    }
  };

  // Atualiza o estado dos campos do formulário
  const handleChange = (event) => {
    const { name, value, type, checked } = event.target;
    setUserData({
      ...userData,
      [name]: type === 'checkbox' ? checked : value,
    });
  };

  function handleNewCode() {
    const token = localStorage.getItem('token');
    fetch('http://miraup.test/json/api/v1/user/new-code', {
      method: 'POST',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data.data);
        if (data.success) {
          setMessage({
            type: 'success',
            text: 'Código de confirmação enviado para o email!',
          });
        } else {
          setMessage({
            type: 'danger',
            text: data.message || 'Erro ao enviar o código.',
          });
        }
      })
      .catch((error) => {
        setMessage({
          type: 'danger',
          text: 'Erro na requisição: ' + error.message,
        });
      });
  }

  return (
    <div className="p-4">
      <h2>Atualizar Dados do Usuário</h2>
      {message.text && <Alert variant={message.type}>{message.text}</Alert>}

      <Form onSubmit={handleSubmit}>
        <Row>
          <Col md={6}>
            <Form.Group className="mb-3">
              <Form.Label>ID do Usuário</Form.Label>
              <Form.Control type="text" value={userData.id} disabled />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Nome de Exibição</Form.Label>
              <Form.Control type="text" value={userData.username} disabled />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Nome</Form.Label>
              <Form.Control
                type="text"
                name="name"
                value={userData.name}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Email</Form.Label>
              <Form.Control
                type="email"
                name="email"
                value={userData.email}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Senha Atual</Form.Label>
              <Form.Control
                type="password"
                name="password"
                value={userData.password}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Nova Senha</Form.Label>
              <Form.Control
                type="password"
                name="password_new"
                value={userData.password_new}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Confirme a Nova Senha</Form.Label>
              <Form.Control
                type="password"
                name="password_confirm"
                value={userData.password_confirm}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Status da Conta</Form.Label>
              <Form.Control
                type="text"
                value={userData.status_account}
                disabled
              />
            </Form.Group>

            {userData.email_confirm && (
              <>
                <Form.Group className="mb-3">
                  <Form.Label>Código de Confirmação de Email</Form.Label>
                  <Form.Control
                    type="text"
                    name="code_email"
                    value={userData.code_email}
                    onChange={handleChange}
                  />
                </Form.Group>
                <Form.Group className="mb-3">
                  <Button onClick={handleNewCode}>Reenviar novo código</Button>
                </Form.Group>
              </>
            )}

            {userData.roles[0] === 'administrator' && (
              <Form.Group className="mb-3">
                <Form.Label>Role</Form.Label>
                <Form.Control
                  type="text"
                  name="role"
                  value={userData.roles[0]}
                  onChange={handleChange}
                />
              </Form.Group>
            )}
          </Col>

          <Col md={6}>
            <Form.Group className="mb-3">
              <Form.Label>Foto de Perfil</Form.Label>
              <img
                src={userData.photo}
                alt="Foto de Perfil"
                style={{
                  width: '100px',
                  display: 'block',
                  marginBottom: '10px',
                }}
              />
              <Form.Control
                type="file"
                onChange={({ target }) => setPhoto(target.files[0])}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Goal: </Form.Label>
              {userData.roles[0] === 'administrator' && (
                <Form.Range
                  name="goal"
                  value={userData.goal}
                  min="0"
                  max="300"
                  onChange={handleChange}
                />
              )}
              <span>{userData.goal}</span>
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="switch"
                name="appearance_system"
                label="Aparência do Sistema"
                checked={userData.appearance_system}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="switch"
                name="notification_asset"
                label="Notificação de Ativos"
                checked={userData.notification_asset}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="switch"
                name="notification_email"
                label="Notificação de Email"
                checked={userData.notification_email}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="switch"
                name="notification_personal"
                label="Notificação Pessoal"
                checked={userData.notification_personal}
                onChange={handleChange}
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Check
                type="switch"
                name="notification_system"
                label="Notificação do Sistema"
                checked={userData.notification_system}
                onChange={handleChange}
              />
            </Form.Group>
          </Col>
        </Row>

        <Button variant="primary" type="submit">
          Atualizar Usuário
        </Button>
      </Form>
    </div>
  );
};

export default UserUpdateForm;
