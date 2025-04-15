import React from 'react';
import { Form, Row, Col, Card, Button, Alert } from 'react-bootstrap';

const UserSearchTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [message, setMessage] = React.useState({ type: '', text: '' }); // Mensagem de sucesso ou erro
  const [searchQuery, setSearchQuery] = React.useState('');
  const [users, setUsers] = React.useState([]);
  const [filters, setFilters] = React.useState({
    search_email: 'true',
    search_username: 'true',
    search_display_name: 'true',
    search_first_name: 'true',
    search_last_name: 'true',
    page: 1,
  });

  // Busca os usuários com base na query e nos filtros
  const searchAssets = async () => {
    try {
      const params = new URLSearchParams({
        search: searchQuery,
        email: filters.search_display_name,
        username: filters.search_username,
        display_name: filters.search_display_name,
        first_name: filters.search_first_name,
        last_name: filters.search_last_name,
        page: filters.page,
      });

      const url = `http://miraup.test/json/api/user-search?${params.toString()}`;

      const response = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error('Falha ao buscar usuário');
      }

      const data = await response.json();

      if (data && data.data) {
        setUsers(data.data);
        setMessage({ type: '', text: '' });
        console.log(data.data);
      }
    } catch (error) {
      setMessage({ type: 'danger', text: `${error}` });
      console.log(error);
    }
  };

  return (
    <Card className="mt-4">
      <Card.Body>
        {message.text && (
          <Alert variant={message.type} className="mt-3">
            {message.text}
          </Alert>
        )}
        <Row>
          <Col>
            <Form.Group controlId="search" className="d-flex gap-3">
              <Form.Control
                type="text"
                name="search"
                placeholder="Pesquisar usuários..."
                value={searchQuery}
                size="lg"
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              <Button onClick={searchAssets}>Buscar</Button>
            </Form.Group>
          </Col>
        </Row>
        <hr />
        <Row>
          <Col>
            <h4>Filtros</h4>
          </Col>
          <Col>
            <Form.Check
              type="switch"
              label="Email"
              checked={filters.search_email}
              onChange={(e) =>
                setFilters({ ...filters, search_email: e.target.checked })
              }
            />
          </Col>
          <Col>
            <Form.Check
              type="switch"
              label="Username"
              checked={filters.search_username}
              onChange={(e) =>
                setFilters({ ...filters, search_username: e.target.checked })
              }
            />
          </Col>
          <Col>
            <Form.Check
              type="switch"
              label="Apelido"
              checked={filters.search_display_name}
              onChange={(e) =>
                setFilters({
                  ...filters,
                  search_display_name: e.target.checked,
                })
              }
            />
          </Col>
          <Col>
            <Form.Check
              type="switch"
              label="Nome"
              checked={filters.search_first_name}
              onChange={(e) =>
                setFilters({ ...filters, search_first_name: e.target.checked })
              }
            />
          </Col>
          <Col>
            <Form.Check
              type="switch"
              label="Sobrenome"
              checked={filters.search_last_name}
              onChange={(e) =>
                setFilters({ ...filters, search_last_name: e.target.checked })
              }
            />
          </Col>
        </Row>
        <hr />
        <h4>Resultados</h4>
        <Row>
          {users.length > 0 &&
            users.map((user) => (
              <Col xs={3} key={user.id} style={{ marginTop: '30px' }}>
                <Card>
                  <Card.Img variant="top" src={user.photo} />
                  <Card.Title className="d-flex justify-content-between">
                    <span className="text-center d-block w-100">
                      {user.id} | {user.display_name}
                    </span>
                  </Card.Title>
                  <Card.Body>
                    <p>e-mail: {user.email}</p>
                    <p>Username: {user.username}</p>
                    <p>Apelido: {user.display_name}</p>
                    <p>
                      Nome: {user.first_name} {user.last_name}
                    </p>
                    <p>status da conta: {user.status_account}</p>
                    <p>Função: {user.role}</p>
                  </Card.Body>
                </Card>
              </Col>
            ))}
        </Row>
      </Card.Body>
    </Card>
  );
};

export default UserSearchTEST;
