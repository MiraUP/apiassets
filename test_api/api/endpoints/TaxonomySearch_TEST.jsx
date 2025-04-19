import React from 'react';
import { Form, Row, Col, Card, Alert, Table } from 'react-bootstrap';

const TaxonomySearchTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [message, setMessage] = React.useState({ type: '', text: '' }); // Mensagem de sucesso ou erro
  const [searchQuery, setSearchQuery] = React.useState('');
  const [taxonomies, setTaxonomies] = React.useState([]);
  const [filters, setFilters] = React.useState({
    taxonomy: 'category',
    name: 'true',
    slug: 'true',
    page: 1,
  });

  // Função para buscar taxonomias
  const searchTaxonomies = async () => {
    try {
      const params = new URLSearchParams({
        search: searchQuery,
        taxonomy: filters.taxonomy,
        name: filters.name,
        slug: filters.slug,
        page: filters.page,
      });

      const url = `http://miraup.test/json/api/v1/taxonomy-search?${params.toString()}`;
      const response = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error('Falha ao buscar taxonomias');
      }

      const data = await response.json();

      if (data && data.data) {
        setTaxonomies(data.data);
        setMessage({ type: '', text: '' });
      }
    } catch (error) {
      setMessage({ type: 'danger', text: `${error}` });
      console.error(error);
    }
  };

  // Efeito para buscar taxonomias automaticamente ao digitar
  React.useEffect(() => {
    if (searchQuery.trim() === '') {
      setTaxonomies([]); // Limpa os resultados se o campo de busca estiver vazio
      return;
    }

    // Debounce para evitar buscas a cada tecla pressionada
    const debounceTimer = setTimeout(() => {
      searchTaxonomies();
    }, 300); // Ajuste o tempo de debounce conforme necessário

    // Limpa o timer se o searchQuery mudar antes do tempo de debounce
    return () => clearTimeout(debounceTimer);
  }, [searchQuery, filters.taxonomy, filters.name, filters.slug, filters.page]);

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
            <Row>
              <Col xs={3}>
                <Form.Group controlId="taxonomy" className="d-flex gap-3">
                  <Form.Select
                    size="lg"
                    value={filters.taxonomy}
                    onChange={(e) =>
                      setFilters({ ...filters, taxonomy: e.target.value })
                    }
                  >
                    <option value="category">Category</option>
                    <option value="post_tag">Post Tag</option>
                    <option value="developer">Developer</option>
                    <option value="origin">Origin</option>
                    <option value="compatibility">Compatibility</option>
                    <option value="icon_category">Icon Category</option>
                    <option value="icon_style">Icon Style</option>
                    <option value="icon_tag">Icon Tag</option>
                    <option value="notification">Notification</option>
                  </Form.Select>
                </Form.Group>
              </Col>
              <Col xs={9}>
                <Form.Group controlId="search" className="d-flex gap-3">
                  <Form.Control
                    type="text"
                    name="search"
                    placeholder="Pesquisar taxonomias..."
                    value={searchQuery}
                    size="lg"
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </Form.Group>
              </Col>
            </Row>
          </Col>
        </Row>
        <hr />
        <Row>
          <Col>
            {taxonomies.length > 0 ? (
              <Table striped bordered hover>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Descrição</th>
                  </tr>
                </thead>
                <tbody>
                  {taxonomies.map(({ id, name, slug, description }) => (
                    <tr key={id}>
                      <td>{id}</td>
                      <td>{name}</td>
                      <td>{slug}</td>
                      <td>{description}</td>
                    </tr>
                  ))}
                </tbody>
              </Table>
            ) : (
              <p>Nenhuma taxonomia encontrada.</p>
            )}
          </Col>
        </Row>
      </Card.Body>
    </Card>
  );
};

export default TaxonomySearchTEST;
