import React, { useState, useEffect } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  Spinner,
  Alert,
  Form,
} from 'react-bootstrap';

const StatisticsDashboard = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [postId, setPostId] = useState('');
  const [userId, setUserId] = React.useState([]);
  const [assets, setAssets] = React.useState([]);
  const [user, setUser] = React.useState([]);

  const fetchStatistics = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch(
        `http://miraup.test/json/api/statistics?user_id=${userId}&post_id=${postId}`,
        {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );

      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }
      console.log(response);
      const data = await response.json();
      setStats(data.data);
      console.log(data.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStatistics();

    fetch('http://miraup.test/json/api/asset?total=-1', {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        setAssets(json.data);
      })
      .catch((error) => console.error('Erro:', error));

    fetch('http://miraup.test/json/api/users', {
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
        console.log(json);
        json.code === 'error' && setError(json.message);
        setUser(json.data);
        return json;
      });
  }, []);

  const handleSubmit = (e) => {
    e.preventDefault();
    fetchStatistics();
  };

  return (
    <Container className="mt-5">
      <h1 className="mb-4">Estatísticas do Usuário</h1>
      <Form onSubmit={handleSubmit} className="mb-4">
        <Row>
          <Col md={6}>
            <Form.Group controlId="postId">
              <Form.Label>ID do Ativo (Opcional)</Form.Label>
              <Form.Select
                value={postId}
                onChange={(e) => setPostId(e.target.value)}
              >
                <option value="">Selecione um Ativo</option>
                {assets.length > 0 &&
                  assets.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.title}
                    </option>
                  ))}
              </Form.Select>
            </Form.Group>
          </Col>
          <Col md={6}>
            <Form.Group controlId="user">
              <Form.Label>Usuário (Opcional)</Form.Label>
              <Form.Select
                value={userId}
                onChange={(e) => setUserId(e.target.value)}
              >
                <option value="">Selecione um usuário</option>
                {user.length > 0 &&
                  user.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.name}
                    </option>
                  ))}
              </Form.Select>
            </Form.Group>
          </Col>
          <Col md={12} className="d-flex align-items-end mt-3">
            <button
              type="submit"
              className="btn btn-primary w-100"
              disabled={loading}
            >
              {loading ? 'Carregando...' : 'Buscar Estatísticas'}
            </button>
          </Col>
        </Row>
      </Form>

      {error && (
        <Alert variant="danger" className="mb-4">
          Erro ao carregar estatísticas: {error}
        </Alert>
      )}

      {loading ? (
        <div className="text-center">
          <Spinner animation="border" role="status">
            <span className="visually-hidden">Carregando...</span>
          </Spinner>
        </div>
      ) : (
        stats && (
          <Row>
            {/* Estatísticas do Usuário */}
            {userId > 0 && stats.user && (
              <Col md={12} className="mb-4">
                <Card>
                  <Card.Header as="h5">
                    Estatísticas do Usuário {stats.user.name}
                  </Card.Header>
                  <Card.Body>
                    <ul className="list-group list-group-flush">
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Ativos Cadastrados
                        <span className="badge bg-primary rounded-pill">
                          {console.log(stats)}
                          {stats.stats.posts_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Ativos Favoritados
                        <span className="badge bg-primary rounded-pill">
                          {stats.stats.favorites_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Downloads
                        <span className="badge bg-primary rounded-pill">
                          {stats.stats.downloads_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Visualizações
                        <span className="badge bg-primary rounded-pill">
                          {stats.stats.views_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Total de interações
                        <span className="badge bg-primary rounded-pill">
                          {stats.stats.total_interactions || 0}
                        </span>
                      </li>
                    </ul>
                  </Card.Body>
                </Card>
              </Col>
            )}
            {/* Estatísticas do Ativo (se post_id foi fornecido) */}
            {postId > 0 && (
              <Col md={12} className="mb-4">
                <Card>
                  <Card.Header as="h5">
                    Estatísticas do Ativo #{stats.post && stats.post.title}
                  </Card.Header>
                  <Card.Body>
                    <ul className="list-group list-group-flush">
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Favoritos
                        <span className="badge bg-success rounded-pill">
                          {stats.stats.favorites_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Visualizações
                        <span className="badge bg-success rounded-pill">
                          {stats.stats.views_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Downloads
                        <span className="badge bg-success rounded-pill">
                          {stats.stats.downloads_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Comentários
                        <span className="badge bg-success rounded-pill">
                          {stats.stats.comments_count || 0}
                        </span>
                      </li>
                      <li className="list-group-item d-flex justify-content-between align-items-center">
                        Total de interações
                        <span className="badge bg-success rounded-pill">
                          {stats.stats.total_interactions || 0}
                        </span>
                      </li>
                    </ul>
                  </Card.Body>
                </Card>
              </Col>
            )}
          </Row>
        )
      )}
    </Container>
  );
};

export default StatisticsDashboard;
