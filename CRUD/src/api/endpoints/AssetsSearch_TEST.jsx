import React from 'react';
import { Form, Row, Col, Card, Button, Alert } from 'react-bootstrap';

const AssetsSearchTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [searchQuery, setSearchQuery] = React.useState('');
  const [assets, setAssets] = React.useState([]);
  const [filters, setFilters] = React.useState({
    author: '',
    category: '',
    compatibility: [],
    developer: '',
    origin: '',
    favorite: false,
  });
  const [message, setMessage] = React.useState({ type: '', text: '' }); // Mensagem de sucesso ou erro
  const [taxonomyName, setTaxonomyName] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [taxonomy, setTaxonomy] = React.useState([
    'category',
    'compatibility',
    'developer',
    'origin',
  ]);
  const [favorites, setFavorites] = React.useState({});

  // Busca os ativos com base na query e nos filtros
  const searchAssets = async () => {
    try {
      const params = new URLSearchParams({
        search: searchQuery,
        author: filters.author,
        category: filters.category,
        compatibility: filters.compatibility,
        developer: filters.developer,
        origin: filters.origin,
        favorite: filters.favorite,
      });

      const url = `http://miraup.test/json/api/asset-search?${params.toString()}`;

      const response = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      console.log(url);

      if (!response.ok) {
        throw new Error('Erro ao buscar ativos');
      }

      const data = await response.json();

      if (data && data.data) {
        setAssets(data.data);
        setMessage({ type: '', text: '' });
        console.log(data.data);
      }
    } catch (error) {
      setMessage({ type: 'danger', text: `Erro ao buscar ativos ${error}` });
    }
  };

  // Autocomplete após 3 caracteres
  React.useEffect(() => {
    if (searchQuery.length >= 3) {
      searchAssets();
    }
  }, [searchQuery]);

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/taxonomy?taxonomy=${taxonomyName}`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setTaxonomyList(json.data);
        json.code && setError(json.message);
        return json;
      });
  }, [taxonomyName]);

  // Função para lidar com o clique no botão de favoritos
  const handleFavorite = (id, currentFavorite) => {
    const newFavorite = !currentFavorite; // Inverte o estado de favorito

    // Atualiza o estado local imediatamente para uma resposta mais rápida
    setFavorites((prev) => ({
      ...prev,
      [id]: newFavorite,
    }));

    // Envia a requisição para a API
    fetch('http://miraup.test/json/api/favorite', {
      method: 'PUT',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        post_id: id,
        favorite: newFavorite,
      }),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => {
        console.log('Resposta da API:', json);

        // Atualiza o estado de favoritos com a resposta da API
        setFavorites((prev) => ({
          ...prev,
          [id]: json.data.favorite,
        }));
      })
      .catch((error) => {
        console.error('Erro:', error);

        // Reverte o estado local em caso de erro
        setFavorites((prev) => ({
          ...prev,
          [id]: currentFavorite,
        }));
      });
  };

  return (
    <>
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
                  placeholder="Pesquisar Ativos Digitais..."
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
            <Col xs="auto">
              <Form.Check
                type="switch"
                label="Favoritos"
                checked={filters.favorite}
                onChange={(e) =>
                  setFilters({ ...filters, favorite: e.target.checked })
                }
              />
            </Col>
          </Row>
          <Row>
            <Col xs={3}>
              <Form.Group controlId="category">
                <Form.Label>Categoria</Form.Label>
                <Form.Select
                  name="category"
                  value={filters.category}
                  onChange={(e) =>
                    setFilters({ ...filters, category: e.target.value })
                  }
                >
                  <option value="">Todas</option>
                  {taxonomyList.length > 0 &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'category')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={term_id}>
                          {name}
                        </option>
                      ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col xs={3}>
              <Form.Group controlId="compatibility">
                <Form.Label>Compatibilidade</Form.Label>
                <Form.Select
                  name="compatibility"
                  value={filters.compatibility}
                  onChange={(e) =>
                    setFilters({ ...filters, compatibility: e.target.value })
                  }
                  multiple="true"
                >
                  <option value="">Todas</option>
                  {taxonomyList.length > 0 &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'compatibility')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={name}>
                          {name}
                        </option>
                      ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col xs={3}>
              <Form.Group controlId="developer">
                <Form.Label>Desenvolvedores</Form.Label>
                <Form.Select
                  name="developer"
                  value={filters.developer}
                  onChange={(e) =>
                    setFilters({ ...filters, developer: e.target.value })
                  }
                >
                  <option value="">Todas</option>
                  {taxonomyList.length > 0 &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'developer')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={name}>
                          {name}
                        </option>
                      ))}
                </Form.Select>
              </Form.Group>
            </Col>

            <Col xs={3}>
              <Form.Group controlId="origin">
                <Form.Label>Origem</Form.Label>
                <Form.Select
                  name="origin"
                  value={filters.origin}
                  onChange={(e) =>
                    setFilters({ ...filters, origin: e.target.value })
                  }
                >
                  <option value="">Todas</option>
                  {taxonomyList.length > 0 &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'origin')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={name}>
                          {name}
                        </option>
                      ))}
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>

          <hr />
          <h4>Resultados</h4>
          <Row>
            {assets.length > 0 ? (
              assets.map((asset) => (
                <Col xs={3} key={asset.id} style={{ marginTop: '30px' }}>
                  <Card>
                    <Card.Img
                      variant="top"
                      src={asset.thumbnail}
                      title={'Criado: ' + asset.date_create}
                    />
                    <Card.Title className="d-flex justify-content-between">
                      <a href={asset.slug}>
                        {asset.id} | {asset.title}
                      </a>
                      <Form.Check
                        type="switch"
                        id={asset.id + '-favorite'}
                        checked={asset.favorite}
                        onChange={() =>
                          handleFavorite(asset.id, favorites[asset.id])
                        }
                      />
                    </Card.Title>
                    <Card.Body>
                      <p>Subtitulo: {asset.subtitle}</p>
                      <p className="d-flex justify-content-between">
                        <span>{asset.developer[0].name}</span>
                        {' | '}
                        <span>{asset.origin[0].name}</span>
                      </p>
                      <div className="d-flex justify-content-between">
                        <Button as="a" href={asset.download}>
                          Download
                        </Button>
                        <div className="d-flex gap-2">
                          {asset.compatibility.length > 0 ? (
                            asset.compatibility.map((comp) => (
                              <Button>{comp.name}</Button>
                            ))
                          ) : (
                            <Button>Sem informação</Button>
                          )}
                        </div>
                      </div>
                    </Card.Body>
                  </Card>
                </Col>
              ))
            ) : (
              <p>Nenhum resultado encontrado</p>
            )}
          </Row>
        </Card.Body>
      </Card>
    </>
  );
};

export default AssetsSearchTEST;
