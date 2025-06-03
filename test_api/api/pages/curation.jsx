import React, { useState, useEffect, use } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  ListGroup,
  Spinner,
  Alert,
  Tab,
  Tabs,
} from 'react-bootstrap';
//import './App.css';

function APIPageCuration() {
  const [posts, setPosts] = useState([]);
  const [selectedPost, setSelectedPost] = useState(null);
  const [latestCuration, setLatestCuration] = useState(null);
  const [curations, setCurations] = useState([]);
  const [selectedCuration, setSelectedCuration] = useState(null);
  const [selectedCurationData, setSelectedCurationData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('posts');

  // Buscar os posts do usuário
  useEffect(() => {
    const fetchUserPosts = async () => {
      setLoading(true);
      setError(null);

      try {
        // Substituir com seu token JWT real
        const token = localStorage.getItem('token');

        const response = await fetch(
          'http://miraup.test/json/api/v1/asset?status=publish,pending,draft',
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );

        if (!response.ok) {
          throw new Error('Falha ao buscar posts do usuário');
        }

        const data = await response.json();
        setPosts(data.data || []);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchUserPosts();
  }, []);

  // Buscar curadorias quando um post é selecionado
  useEffect(() => {
    if (!selectedPost) return;

    const fetchCurations = async () => {
      setLoading(true);
      setError(null);
      setLatestCuration(null);
      setCurations([]);
      setSelectedCuration(null);

      try {
        const token = localStorage.getItem('token');

        // Buscar a última curadoria
        const latestResponse = await fetch(
          `http://miraup.test/json/api/v1/curations/latest?post_id=${selectedPost.id}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );

        if (!latestResponse.ok) {
          throw new Error('Falha ao buscar última curadoria');
        }

        const latestData = await latestResponse.json();
        setLatestCuration(latestData.data);

        // Buscar todas as curadorias
        const allResponse = await fetch(
          `http://miraup.test/json/api/v1/curations?post_id=${selectedPost.id}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );

        if (!allResponse.ok) {
          throw new Error('Falha ao buscar histórico de curadorias');
        }

        const allData = await allResponse.json();
        setCurations(allData.data || []);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchCurations();
  }, [selectedPost]);

  // Busca Curadoria selecionada
  useEffect(() => {
    if (!selectedCuration) return;

    const fetchCurationDetails = async () => {
      setLoading(true);
      setError(null);

      try {
        const token = localStorage.getItem('token');

        const response = await fetch(
          `http://miraup.test/json/api/v1/curations/${selectedCuration.slug}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );
        if (!response.ok) {
          throw new Error('Falha ao buscar detalhes da curadoria');
        }

        const data = await response.json();
        setSelectedCurationData(data.data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchCurationDetails();
  }, [selectedCuration]);

  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (status) => {
    let variant = 'secondary';
    let text = status;

    if (status === 'publish') {
      variant = 'success';
      text = 'Publicado';
    } else if (status === 'pending') {
      variant = 'warning';
      text = 'Pendente';
    } else if (status === 'draft') {
      variant = 'danger';
      text = 'Rascunho';
    }

    return <span className={`badge bg-${variant}`}>{text}</span>;
  };

  return (
    <Container className="mt-4 mb-5">
      <h1 className="text-center mb-4">Curadoria de Conteúdo</h1>

      {error && (
        <Alert variant="danger" className="mb-4">
          <strong>Erro:</strong> {error}
        </Alert>
      )}

      <Tabs
        activeKey={activeTab}
        onSelect={(k) => setActiveTab(k)}
        className="mb-3"
      >
        <Tab eventKey="posts" title="Meus Posts">
          <Row className="mt-3">
            <Col lg={5}>
              <Card className="mb-4">
                <Card.Header className="bg-primary text-white">
                  <Card.Title>Meus Posts</Card.Title>
                </Card.Header>
                <Card.Body>
                  {loading ? (
                    <div className="text-center">
                      <Spinner animation="border" />
                      <p className="mt-2">Carregando posts...</p>
                    </div>
                  ) : posts.length === 0 ? (
                    <Alert variant="info">
                      Você ainda não possui posts cadastrados.
                    </Alert>
                  ) : (
                    <ListGroup>
                      {posts.map((post) => (
                        <ListGroup.Item
                          key={post.id}
                          action
                          active={selectedPost?.id === post.id}
                          onClick={() => setSelectedPost(post)}
                          className="d-flex justify-content-between align-items-center"
                        >
                          <div className="text-truncate me-2">{post.title}</div>
                          {getStatusBadge(post.status)}
                        </ListGroup.Item>
                      ))}
                    </ListGroup>
                  )}
                </Card.Body>
              </Card>
            </Col>

            <Col lg={7}>
              {selectedPost ? (
                <>
                  <Card className="mb-4">
                    <Card.Header className="bg-info text-white">
                      <Card.Title>Post Selecionado</Card.Title>
                    </Card.Header>
                    <Card.Body>
                      <h5>{selectedPost.title}</h5>
                      <p className="mb-1">
                        <strong>Status:</strong>{' '}
                        {getStatusBadge(selectedPost.status)}
                      </p>
                      <p className="mb-1">
                        <strong>Autor:</strong> {selectedPost.author}
                      </p>
                      <p className="mb-1">
                        <strong>Criado em:</strong>{' '}
                        {formatDate(selectedPost.date_create)}
                      </p>
                      <p className="mb-0">
                        <strong>Atualizado em:</strong>{' '}
                        {formatDate(selectedPost.update)}
                      </p>
                    </Card.Body>
                  </Card>

                  {loading ? (
                    <div className="text-center">
                      <Spinner animation="border" />
                      <p className="mt-2">Carregando curadorias...</p>
                    </div>
                  ) : (
                    <>
                      <Card className="mb-4">
                        <Card.Header className="bg-success text-white">
                          <Card.Title>Última Curadoria</Card.Title>
                        </Card.Header>
                        <Card.Body>
                          {latestCuration ? (
                            <>
                              <h5>{latestCuration.post_title}</h5>
                              <p className="mb-1">
                                <strong>Status:</strong>{' '}
                                {getStatusBadge(latestCuration.status)}
                              </p>
                              <p className="mb-1">
                                <strong>Data:</strong>{' '}
                                {formatDate(latestCuration.created_at)}
                              </p>
                              <p className="mb-0">
                                <strong>Mensagem:</strong>{' '}
                                {latestCuration.short_message ||
                                  'Nenhuma mensagem disponível'}
                              </p>
                            </>
                          ) : (
                            <Alert variant="info">
                              Nenhuma curadoria encontrada para este post.
                            </Alert>
                          )}
                        </Card.Body>
                      </Card>

                      <Card>
                        <Card.Header className="bg-secondary text-white">
                          <Card.Title>Histórico de Curadorias</Card.Title>
                        </Card.Header>
                        <Card.Body>
                          {curations.length === 0 ? (
                            <Alert variant="info">
                              Nenhum histórico de curadoria disponível.
                            </Alert>
                          ) : (
                            <ListGroup>
                              {curations.map((curation) => (
                                <ListGroup.Item
                                  key={curation.id}
                                  action
                                  active={selectedCuration?.id === curation.id}
                                  onClick={() => setSelectedCuration(curation)}
                                  className="d-flex justify-content-between align-items-center"
                                >
                                  <div>
                                    <div className="fw-bold">
                                      {curation.post_title}
                                    </div>
                                    <div className="small">
                                      {formatDate(curation.created_at)}
                                    </div>
                                  </div>
                                  {getStatusBadge(curation.status)}
                                </ListGroup.Item>
                              ))}
                            </ListGroup>
                          )}
                        </Card.Body>
                      </Card>
                    </>
                  )}
                </>
              ) : (
                <Card>
                  <Card.Header className="bg-light">
                    <Card.Title>Selecione um Post</Card.Title>
                  </Card.Header>
                  <Card.Body>
                    <p className="text-muted">
                      Selecione um post na lista ao lado para ver suas
                      curadorias.
                    </p>
                  </Card.Body>
                </Card>
              )}
            </Col>
          </Row>
        </Tab>

        <Tab eventKey="curation" title="Detalhes da Curadoria">
          {selectedCuration ? (
            <Row className="mt-3">
              <Col lg={8} className="mx-auto">
                <Card>
                  <Card.Header className="bg-primary text-white">
                    <Card.Title>Detalhes da Curadoria</Card.Title>
                  </Card.Header>
                  <Card.Body>
                    <h4 className="mb-4">{selectedCuration.post_title}</h4>

                    <div className="d-flex justify-content-between mb-3">
                      <div>
                        <h6>Status</h6>
                        {getStatusBadge(selectedCuration.status)}
                      </div>

                      <div>
                        <h6>Data</h6>
                        <p className="mb-0">
                          {formatDate(selectedCuration.created_at)}
                        </p>
                      </div>
                    </div>

                    <div className="mb-4">
                      <h6>Autor</h6>
                      <p className="mb-0">
                        {console.log(selectedCuration)}
                        {selectedCuration.curator?.name || 'N/A'}
                        {selectedCuration.curator?.id &&
                          ` (ID: ${selectedCuration.curator.id})`}
                      </p>
                    </div>

                    <div className="mb-4">
                      <h6>Mensagem Curta aqui</h6>
                      <div className="alert alert-info">
                        {selectedCuration.short_message ||
                          'Nenhuma mensagem curta disponível'}
                      </div>
                    </div>

                    <div>
                      <h6>Conteúdo Completo</h6>
                      <div className="border p-3 bg-light rounded">
                        {selectedCuration.content ||
                          'Nenhum conteúdo detalhado disponível'}
                      </div>
                    </div>
                  </Card.Body>
                  <Card.Footer className="text-muted small">
                    ID da Curadoria: {selectedCuration.id} | Post ID:{' '}
                    {selectedCuration.post_id}
                  </Card.Footer>
                </Card>
              </Col>
            </Row>
          ) : (
            <Row className="mt-3">
              <Col lg={8} className="mx-auto">
                <Card>
                  <Card.Header className="bg-light">
                    <Card.Title>Detalhes da Curadoria</Card.Title>
                  </Card.Header>
                  <Card.Body>
                    <p className="text-muted text-center">
                      Selecione uma curadoria no histórico para ver os detalhes
                      completos.
                    </p>
                  </Card.Body>
                </Card>
              </Col>
            </Row>
          )}
        </Tab>
      </Tabs>
    </Container>
  );
}

export default APIPageCuration;
