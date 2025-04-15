import React from 'react';
import { Form, Row, Col, Card, Button, Alert } from 'react-bootstrap';

const MediaSearchTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [searchQuery, setSearchQuery] = React.useState('');
  const [assets, setAssets] = React.useState([]);
  const [medias, setMedias] = React.useState([]);
  const [postId, setPostId] = React.useState('');
  const [taxonomyName, setTaxonomyName] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [filters, setFilters] = React.useState({
    post_id: '',
    title: '',
    icon_category: '',
    icon_style: '',
  });
  const [message, setMessage] = React.useState({ type: '', text: '' }); // Mensagem de sucesso ou erro

  // Busca os ativos com base na query e nos filtros
  const searchAssets = async () => {
    try {
      const params = new URLSearchParams({
        search: searchQuery,
        post_id: postId,
        icon_category: filters.icon_category,
        icon_style: filters.icon_style,
      });

      const url = `http://miraup.test/json/api/media-search?${params.toString()}`;

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
        setMedias(data.data);
        setMessage({ type: '', text: '' });
        console.log(data.data);
      }
    } catch (error) {
      setMessage({ type: 'danger', text: `Erro ao buscar ativos ${error}` });
    }
  };

  // Autocomplete após 1 caracter
  React.useEffect(() => {
    if (searchQuery.length >= 1) {
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

  React.useEffect(() => {
    fetch('http://miraup.test/json/api/asset?total=-1', {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        json.code === 'error' && setError(json.message);
        setAssets(json.data);
        return json.data;
      });
  }, []);

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
            <Col xs={3}>
              <Form.Group>
                <Form.Control
                  as="select"
                  size="lg"
                  value={postId}
                  onChange={(e) => setPostId(e.target.value)}
                >
                  <option value="">Selecione um ativo...</option>
                  {assets.length > 0 &&
                    assets.map((asset) => (
                      <option key={asset.id} value={asset.id}>
                        {asset.title}
                      </option>
                    ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col xs={9}>
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
            <Col xs={6}>
              <Form.Group>
                <Form.Control
                  as="select"
                  size="lg"
                  value={filters.icon_category}
                  onChange={(e) =>
                    setFilters({ ...filters, icon_category: e.target.value })
                  }
                >
                  <option value="">Selecione uma categoria...</option>
                  {taxonomyList &&
                    taxonomyList.length > 0 &&
                    taxonomyList != Array.isArray(taxonomyList) &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'icon_category')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={name}>
                          {name}
                        </option>
                      ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col xs={6}>
              <Form.Group>
                <Form.Control
                  as="select"
                  size="lg"
                  value={filters.icon_style}
                  onChange={(e) =>
                    setFilters({ ...filters, icon_style: e.target.value })
                  }
                >
                  <option value="">Selecione um estilo...</option>
                  {taxonomyList &&
                    taxonomyList.length > 0 &&
                    taxonomyList != Array.isArray(taxonomyList) &&
                    taxonomyList
                      .filter(({ taxonomy }) => taxonomy === 'icon_style')
                      .map(({ term_id, name }) => (
                        <option key={term_id} value={name}>
                          {name}
                        </option>
                      ))}
                </Form.Control>
              </Form.Group>
            </Col>
          </Row>
          <hr />
          <h4>Resultados</h4>
          <Row>
            {medias.length > 0 ? (
              medias.map((media) => (
                <Col xs={3} key={media.id} style={{ marginTop: '30px' }}>
                  <Card>
                    <Card.Img
                      variant="top"
                      src={media.url}
                      title={media.mime_type}
                    />
                    <Card.Body style={{ textAlign: 'center' }}>
                      {media.title}
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

export default MediaSearchTEST;
