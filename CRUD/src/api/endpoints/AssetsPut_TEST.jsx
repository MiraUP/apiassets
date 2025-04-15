import React, { useState, useEffect } from 'react';
import {
  Container,
  Form,
  Row,
  Col,
  Card,
  Button,
  Image,
  Alert,
} from 'react-bootstrap';

const PostForm = ({ post, token }) => {
  const [formData, setFormData] = useState({
    title: post.title,
    subtitle: post.subtitle,
    content: post.post_content,
    category: post.category[0]?.term_id || '',
    origin: post.origin[0]?.name || '',
    developer: post.developer[0]?.name || '',
    version: post.version,
    download: post.download,
    font: post.font,
    size_file: post.size_file,
    post_tag: post.post_tag ? post.post_tag.map((tag) => tag.name) : [],
    compatibility: post.compatibility
      ? post.compatibility.map((comp) => comp.name)
      : [],
  });
  const [emphasisList, setEmphasisList] = useState(
    post.emphasis
      ? post.emphasis.map((emphasis) => ({
          meta_id: emphasis.id, // meta_id dos emphasis já cadastrados
          meta_value: emphasis.value, // meta_value dos emphasis já cadastrados
        }))
      : [],
  ); // Lista de emphasis
  const [categories, setCategories] = useState([]); // Lista de categorias
  const [origins, setOrigins] = useState([]); // Lista de origens
  const [developers, setDevelopers] = useState([]); // Lista de desenvolvedores
  const [compatibilities, setCompatibilities] = useState([]); // Lista de compatibilidades
  const [tags, setTags] = useState([]); // Lista de tags
  const [message, setMessage] = useState({ type: '', text: '' }); // Mensagem de sucesso ou erro
  const [thumbnail, setThumbnail] = useState(null); // Imagem de thumbnail selecionada
  const [previews, setPreviews] = useState([]); // Lista de imagens de previews selecionadas

  // Busca as taxonomias ao carregar o componente
  useEffect(() => {
    const fetchTaxonomies = async (taxonomy, setState) => {
      const response = await fetch(
        `http://miraup.test/json/api/taxonomy?taxonomy=${taxonomy}`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );
      const data = await response.json();
      if (data.success) {
        setState(data.data);
      }
    };

    fetchTaxonomies('category', setCategories);
    fetchTaxonomies('origin', setOrigins);
    fetchTaxonomies('developer', setDevelopers);
    fetchTaxonomies('compatibility', setCompatibilities);
    fetchTaxonomies('post_tag', setTags);
  }, [post, token]);

  // Atualiza o estado do formulário quando os dados mudam
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  // Atualiza o estado para selects múltiplos
  const handleMultipleSelectChange = (e) => {
    const { name, options } = e.target;
    const selectedValues = Array.from(options)
      .filter((option) => option.selected)
      .map((option) => option.value);
    setFormData({
      ...formData,
      [name]: selectedValues,
    });
  };

  // Adiciona um novo emphasis à lista
  const handleAddEmphasis = () => {
    setEmphasisList([...emphasisList, { meta_id: null, meta_value: '' }]);
  };

  // Remove um emphasis da lista
  const handleRemoveEmphasis = (index, emphasis) => {
    const updatedList = emphasisList.filter((_, i) => i !== index);
    setEmphasisList(updatedList);

    fetch('http://miraup.test/json/api/asset-put', {
      method: 'POST',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        post_id: post.id,
        delete_emphasis: emphasis,
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
        return json;
      })
      .catch((error) => {
        console.error('Erro:', error);
      });
  };

  // Atualiza o valor de um emphasis na lista
  const handleEmphasisChange = (index, value) => {
    const updatedList = emphasisList.map((emphasis, i) =>
      i === index ? { ...emphasis, meta_value: value } : emphasis,
    );
    setEmphasisList(updatedList);
  };

  // Atualiza a imagem de thumbnail
  const handleThumbnailChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setThumbnail(file);
    }
  };

  // Atualiza a lista de previews
  const handlePreviewsChange = (e) => {
    const files = Array.from(e.target.files);
    setPreviews(files);
  };

  // Remove uma imagem da lista de previews
  const handleRemovePreview = (index) => {
    const updatedPreviews = previews.filter((_, i) => i !== index);
    setPreviews(updatedPreviews);

    fetch(
      `http://miraup.test/json/api/media?asset_id=${post.id}&media_id=${index}`,
      {
        method: 'DELETE',
        headers: {
          Authorization: 'Bearer ' + token,
        },
      },
    )
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => {
        console.log('Resposta da API:', json);
        return json;
      })
      .catch((error) => {
        console.error('Erro:', error);
      });
  };

  // Envia os dados atualizados para a API
  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      // Cria um array com todos os emphasis (existentes e novos)
      const emphasisToSend = emphasisList
        .filter((emphasis) => emphasis.meta_value) // Filtra emphasis com valor
        .map((emphasis) => ({
          meta_id: emphasis.meta_id || null, // meta_id pode ser null para novos emphasis
          meta_value: emphasis.meta_value,
        }));

      // Cria um FormData para enviar os arquivos
      const formDataToSend = new FormData();
      formDataToSend.append('post_id', post.id);
      formDataToSend.append('title', formData.title);
      formDataToSend.append('subtitle', formData.subtitle);
      formDataToSend.append('content', formData.content);
      formDataToSend.append('category', formData.category);
      formDataToSend.append('origin', formData.origin);
      formDataToSend.append('developer', formData.developer);
      formDataToSend.append('version', formData.version);
      formDataToSend.append('download', formData.download);
      formDataToSend.append('font', formData.font);
      formDataToSend.append('size_file', formData.size_file);
      formDataToSend.append('post_tag', JSON.stringify(formData.post_tag));
      formDataToSend.append(
        'compatibility',
        JSON.stringify(formData.compatibility),
      );
      formDataToSend.append('emphasis', JSON.stringify(emphasisToSend)); // Envia todos os emphasis

      // Adiciona a thumbnail ao FormData, se existir
      if (thumbnail) {
        formDataToSend.append('thumbnail', thumbnail);
      }

      // Adiciona os previews ao FormData
      for (let i = 0; i < previews.length; i++) {
        formDataToSend.append(`previews${[i]}`, previews[i]);
      }

      const response = await fetch('http://miraup.test/json/api/asset-put', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
        },
        body: formDataToSend,
      });

      const data = await response.json();
      console.log(data.data);
      if (data.success) {
        setMessage({ type: 'success', text: 'Post atualizado com sucesso!' });
      } else {
        setMessage({
          type: 'danger',
          text: data.message || 'Erro ao atualizar o post.',
        });
      }
    } catch (error) {
      setMessage({ type: 'danger', text: 'Erro na conexão com a API.' });
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
        <Form onSubmit={handleSubmit}>
          <Row>
            <Col md={6}>
              <Form.Group controlId="title">
                <Form.Label>{post.id} | Título</Form.Label>
                <Form.Control
                  type="text"
                  name="title"
                  value={formData.title}
                  onChange={handleInputChange}
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="subtitle">
                <Form.Label>Subtítulo</Form.Label>
                <Form.Control
                  type="text"
                  name="subtitle"
                  value={formData.subtitle}
                  onChange={handleInputChange}
                />
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col md={3}>
              <Form.Group controlId="slug">
                <Form.Label>Slug</Form.Label>
                <Form.Control type="text" defaultValue={post.slug} disabled />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group controlId="developers">
                <Form.Label>Último Update</Form.Label>
                <Form.Control type="text" defaultValue={post.update} disabled />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group controlId="author">
                <Form.Label>Autor</Form.Label>
                <Form.Control type="text" defaultValue={post.author} disabled />
              </Form.Group>
            </Col>
            <Col md={3}>
              <Form.Group controlId="status">
                <Form.Label>Status</Form.Label>
                <Form.Control type="text" defaultValue={post.status} disabled />
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col md={6}>
              <Form.Group controlId="thumbnail">
                <Form.Label>Thumbnail</Form.Label>
                {post.thumbnail && !thumbnail && (
                  <Image src={post.thumbnail} fluid className="mb-2" />
                )}
                {thumbnail && (
                  <Image
                    src={URL.createObjectURL(thumbnail)}
                    fluid
                    className="mb-2"
                  />
                )}
                <Form.Control type="file" onChange={handleThumbnailChange} />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="previews">
                <Form.Label>Previews</Form.Label>
                <Row>
                  {post.previews.map((preview, index) => (
                    <Col
                      key={index}
                      xs={6}
                      className="d-flex flex-column gap-1"
                      style={{ marginBottom: '30px' }}
                    >
                      <Image
                        src={preview.url}
                        fluid
                        className="mb-2"
                        style={{ height: '150px' }}
                      />
                      <Button
                        variant="danger"
                        className="w-100"
                        onClick={() => handleRemovePreview(preview.id)}
                      >
                        Excluir
                      </Button>
                    </Col>
                  ))}
                  {previews.map((file, index) => (
                    <Col
                      key={index}
                      xs={6}
                      className="d-flex flex-column gap-1"
                      style={{ marginBottom: '30px' }}
                    >
                      <Image
                        src={URL.createObjectURL(file)}
                        fluid
                        className="mb-2"
                        style={{ height: '150px' }}
                      />
                      <Button
                        variant="danger"
                        className="w-100"
                        onClick={() => handleRemovePreview(index)}
                      >
                        Excluir
                      </Button>
                    </Col>
                  ))}
                </Row>
                <Form.Control
                  type="file"
                  multiple
                  onChange={handlePreviewsChange}
                />
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col>
              <Form.Group controlId="content">
                <Form.Label>Conteúdo</Form.Label>
                <Form.Control
                  as="textarea"
                  rows={5}
                  name="content"
                  value={formData.content}
                  onChange={handleInputChange}
                />
              </Form.Group>
            </Col>
          </Row>
          <Row>
            <Col md={6}>
              <Form.Group controlId="category">
                <Form.Label>Categoria</Form.Label>
                <Form.Control
                  as="select"
                  name="category"
                  value={formData.category}
                  onChange={handleInputChange}
                >
                  {categories.map((category) => (
                    <option key={category.term_id} value={category.term_id}>
                      {category.name}
                    </option>
                  ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="origin">
                <Form.Label>Origin</Form.Label>
                <Form.Control
                  as="select"
                  name="origin"
                  value={formData.origin}
                  onChange={handleInputChange}
                >
                  {origins.map((origin) => (
                    <option key={origin.term_id} value={origin.name}>
                      {origin.name}
                    </option>
                  ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="developers">
                <Form.Label>Developers</Form.Label>
                <Form.Control
                  as="select"
                  name="developer"
                  value={formData.developer}
                  onChange={handleInputChange}
                >
                  {developers.map((developers) => (
                    <option key={developers.term_id} value={developers.name}>
                      {developers.name}
                    </option>
                  ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="version">
                <Form.Label>Version</Form.Label>
                <Form.Control
                  type="text"
                  name="version"
                  value={formData.version}
                  onChange={handleInputChange}
                />
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="compatibility">
                <Form.Label>Compatibilidade</Form.Label>
                <Form.Control
                  as="select"
                  name="compatibility"
                  multiple
                  value={formData.compatibility}
                  onChange={handleMultipleSelectChange}
                >
                  {compatibilities.map((compatibility) => (
                    <option
                      key={compatibility.term_id}
                      value={compatibility.name}
                    >
                      {compatibility.name}
                    </option>
                  ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Form.Group controlId="post_tag">
                <Form.Label>Tags</Form.Label>
                <Form.Control
                  as="select"
                  name="post_tag"
                  multiple
                  value={formData.post_tag}
                  onChange={handleMultipleSelectChange}
                >
                  {tags.map((tag) => (
                    <option key={tag.term_id} value={tag.name}>
                      {tag.name}
                    </option>
                  ))}
                </Form.Control>
              </Form.Group>
            </Col>
            <Col md={6}>
              <Card className="mt-4">
                <Card.Body>
                  <Form.Group controlId="emphasis">
                    <Form.Label>Emphasis</Form.Label>
                    {emphasisList.map((emphasis, index) => (
                      <Row key={index} className="mb-2">
                        <Col xs="auto">{index + 1}</Col>
                        <Col>
                          <Form.Control
                            type="text"
                            value={emphasis.meta_value}
                            onChange={(e) =>
                              handleEmphasisChange(index, e.target.value)
                            }
                          />
                        </Col>
                        <Col xs="auto">
                          <Button
                            variant="danger"
                            onClick={() =>
                              handleRemoveEmphasis(index, emphasis.meta_value)
                            }
                          >
                            Excluir
                          </Button>
                        </Col>
                      </Row>
                    ))}
                    <Button
                      variant="success"
                      onClick={handleAddEmphasis}
                      className="w-100"
                    >
                      Adicionar Emphasis
                    </Button>
                  </Form.Group>
                </Card.Body>
              </Card>
            </Col>
            <Col xs={6}>
              <Row className="flex-column gap-3">
                <Col>
                  <Form.Group controlId="download">
                    <Form.Label>Download</Form.Label>
                    <Form.Control
                      type="text"
                      name="download"
                      value={formData.download}
                      onChange={handleInputChange}
                    />
                  </Form.Group>
                </Col>
                <Col>
                  <Form.Group controlId="font">
                    <Form.Label>Font</Form.Label>
                    <Form.Control
                      type="text"
                      name="font"
                      value={formData.font}
                      onChange={handleInputChange}
                    />
                  </Form.Group>
                </Col>
                <Col>
                  <Form.Group controlId="size_file">
                    <Form.Label>Size File</Form.Label>
                    <Form.Control
                      type="text"
                      name="size_file"
                      value={formData.size_file}
                      onChange={handleInputChange}
                    />
                  </Form.Group>
                </Col>
                <Col>
                  <Form.Group controlId="entry">
                    <Form.Label>Entry</Form.Label>
                    <Form.Control
                      type="text"
                      defaultValue={post.entry}
                      disabled
                    />
                  </Form.Group>
                </Col>
              </Row>
            </Col>
          </Row>
          {/* Adicione os demais campos aqui */}
          <Button
            variant="primary"
            size="lg"
            type="submit"
            className="mt-3 w-100"
          >
            Atualizar
          </Button>
        </Form>
      </Card.Body>
    </Card>
  );
};

const AssetsGetTEST = () => {
  const [posts, setPosts] = useState([]); // Lista de posts
  const [selectedPost, setSelectedPost] = useState(null); // Post selecionado
  const [token, setToken] = useState(localStorage.getItem('token') || ''); // Token de autenticação
  const [selectedSlug, setSelectedSlug] = useState(''); // Slug do post selecionado

  // Busca a lista de posts ao carregar o componente
  useEffect(() => {
    fetch('http://miraup.test/json/api/asset', {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          setPosts(data.data);
        }
      })
      .catch((error) => console.error('Erro ao buscar posts:', error));
  }, [token]);

  // Atualiza o post selecionado quando o slug muda
  useEffect(() => {
    if (selectedSlug) {
      // Reseta o post selecionado antes de buscar os novos dados
      setSelectedPost(null);

      fetch(`http://miraup.test/json/api/asset/${selectedSlug}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            setSelectedPost(data.data);
            console.log(data.data);
          }
        })
        .catch((error) => console.error('Erro ao buscar post:', error));
    } else {
      setSelectedPost(null); // Reseta o post selecionado se nenhum slug for selecionado
    }
  }, [selectedSlug, token]);

  // Atualiza o slug selecionado quando o usuário muda o combobox
  const handlePostSelect = (event) => {
    const slug = event.target.value;
    setSelectedSlug(slug);
  };

  return (
    <Container className="mt-4">
      <h1 className="mb-4">Atualizar Ativo Digital</h1>
      <Row>
        <Col md={4}>
          <Form.Group controlId="postSelect">
            <Form.Label>Selecione um Post</Form.Label>
            <Form.Control as="select" onChange={handlePostSelect}>
              <option value="">Selecione...</option>
              {posts.map((post) => (
                <option key={post.id} value={post.slug}>
                  {post.title}
                </option>
              ))}
            </Form.Control>
          </Form.Group>
        </Col>
      </Row>
      {selectedPost && <PostForm post={selectedPost} token={token} />}
    </Container>
  );
};

export default AssetsGetTEST;
