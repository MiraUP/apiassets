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
  const [message, setMessage] = useState({ type: '', text: '' }); // Mensagem de sucesso ou erro
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

  // Atualiza o estado do formulário quando os dados mudam
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      // Cria um FormData para enviar os arquivos
      const formDataToSend = new FormData();
      formDataToSend.append('post_id', post.id);
      formDataToSend.append('title', formData.title);

      const response = await fetch('http://miraup.test/json/api/v1/asset-put', {
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
          <Alert
            variant={message.type}
            className="mt-3"
            onClose={() => setMessage({ type: '', text: '' })}
          >
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
          </Row>
        </Form>
      </Card.Body>
    </Card>
  );
};

const AssetsPutTEST = () => {
  const [posts, setPosts] = useState([]); // Lista de posts
  const [selectedPost, setSelectedPost] = useState(null); // Post selecionado
  const [token, setToken] = useState(localStorage.getItem('token') || ''); // Token de autenticação
  const [selectedSlug, setSelectedSlug] = useState(''); // Slug do post selecionado

  // Busca a lista de posts ao carregar o componente
  useEffect(() => {
    fetch('http://miraup.test/json/api/v1/asset', {
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

      fetch(`http://miraup.test/json/api/v1/asset/${selectedSlug}`, {
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

export default AssetsPutTEST;
