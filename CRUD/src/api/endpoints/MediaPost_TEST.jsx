import React, { useState, useEffect } from 'react';
import { Button, Col, Form, Row, Alert } from 'react-bootstrap';
//import 'bootstrap/dist/css/bootstrap.min.css';

const MediaPostTEST = () => {
  const [posts, setPosts] = useState([]); // Lista de posts
  const [selectedPostId, setSelectedPostId] = useState(''); // ID do post selecionado
  const [files, setFiles] = useState([]); // Arquivos selecionados
  const [token, setToken] = useState(''); // Token de autenticação
  const [taxonomyName, setTaxonomyName] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]); // Lista de taxonomias
  const [fileData, setFileData] = useState([]); // Dados das imagens (preview e taxonomias)
  const [message, setMessage] = useState({ type: '', text: '' }); // Mensagem de sucesso ou erro

  // Busca o token do localStorage ao carregar o componente
  useEffect(() => {
    const storedToken = localStorage.getItem('token');
    if (storedToken) {
      setToken(storedToken);
    }
  }, []);

  // Busca a lista de posts ao carregar o componente
  useEffect(() => {
    const fetchPosts = async () => {
      try {
        const response = await fetch('http://miraup.test/json/api/asset/', {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          throw new Error('Erro ao buscar posts');
        }

        const data = await response.json();
        setPosts(data.data);
      } catch (error) {
        console.error('Erro:', error);
      }
    };

    if (token) {
      fetchPosts();
    }
  }, [token]);

  // Busca as taxonomias ao carregar o componente
  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/taxonomy?taxonomy=${taxonomyName}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        if (json.success) {
          setTaxonomyList(json.data);
          setMessage({
            type: 'success',
            text: 'Taxonomias listadas!',
          });
        } else {
          setMessage({
            type: 'danger',
            text: json.message || 'Erro ao buscar taxonomias.',
          });
        }
      })
      .catch((error) => {
        setMessage({
          type: 'danger',
          text: 'Erro na requisição: ' + error.message,
        });
      });
  }, [taxonomyName, token]);

  // Atualiza os dados das imagens quando novos arquivos são selecionados
  useEffect(() => {
    if (files.length > 0) {
      const newFileData = files.map((file) => ({
        file,
        preview: URL.createObjectURL(file),
        icon_category: [],
        icon_style: '',
        icon_tag: [],
      }));
      setFileData(newFileData);
    }
  }, [files]);

  // Manipula o envio do formulário
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!selectedPostId || files.length === 0) {
      setMessage({
        type: 'danger',
        text: 'Selecione um post e pelo menos uma imagem.',
      });
      return;
    }

    try {
      const results = [];

      const formData = new FormData();
      formData.append('post_id', selectedPostId); // ID do post

      fileData.forEach((file, index) => {
        formData.append(`preview[${index}]`, file.file); // Arquivos de imagem

        // Adiciona as categorias como strings separadas por vírgulas
        if (file.icon_category.length > 0) {
          formData.append(
            `icon_category[${index}]`,
            file.icon_category.join(','),
          );
        }

        // Adiciona o estilo
        if (file.icon_style) {
          formData.append(`icon_style[${index}]`, file.icon_style);
        }

        // Adiciona as tags como strings separadas por vírgulas
        if (file.icon_tag.length > 0) {
          formData.append(`icon_tag[${index}]`, file.icon_tag.join(','));
        }
      });

      const response = await fetch('http://miraup.test/json/api/media', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Erro completo:', errorText); // Exibe o erro no console
        throw new Error(
          `Erro na requisição: ${response.status} - ${response.statusText}\n${errorText}`,
        );
      }

      const result = await response.json();
      results.push(result.data);
      console.log(result.data);

      setMessage({
        type: 'success',
        text: 'Mídias enviadas com sucesso! IDs: ' + results.join(', '),
      });
    } catch (error) {
      console.error('Erro no fetch:', error.message);
      setMessage({ type: 'danger', text: 'Falha ao enviar mídias.' });
    }
  };

  // Atualiza os valores das taxonomias para uma imagem específica
  const handleTaxonomyChange = (index, taxonomy, value) => {
    const updatedFileData = [...fileData];
    updatedFileData[index][taxonomy] = value;
    setFileData(updatedFileData);
  };

  return (
    <div className="container mt-5">
      <h1 className="mb-4">Upload de Mídias</h1>
      {message.text && (
        <Alert variant={message.type} className="mt-3">
          {message.text}
        </Alert>
      )}
      <form onSubmit={handleSubmit}>
        <div className="mb-3">
          <label htmlFor="postSelect" className="form-label">
            Selecione um Post
          </label>
          <select
            id="postSelect"
            className="form-select"
            value={selectedPostId}
            onChange={(e) => setSelectedPostId(e.target.value)}
            required
          >
            <option value="">Selecione...</option>
            {posts.length > 0 &&
              posts.map((post) => (
                <option key={post.id} value={post.id}>
                  {post.title} (ID: {post.id})
                </option>
              ))}
          </select>
        </div>

        <div className="mb-3">
          <label htmlFor="fileInput" className="form-label">
            Selecione as Imagens
          </label>
          <input
            id="fileInput"
            type="file"
            className="form-control"
            multiple
            onChange={(e) => setFiles([...e.target.files])}
            required
          />
        </div>

        <Row className="mb-4 p-3">
          {/* Preview das Imagens e Inputs de Taxonomias */}
          {fileData.map((data, index) => (
            <Col xs={3} key={index} className="mb-4 p-3 border rounded">
              <Col xs="auto">
                <img
                  src={data.preview}
                  alt={`Preview ${index}`}
                  className="img-thumbnail mb-3"
                  style={{ maxWidth: '200px', maxHeight: '200px' }}
                />
              </Col>
              <Col>
                <div className="mb-3 d-flex gap-3">
                  <label
                    className="form-label d-inline-block"
                    style={{ width: '100px' }}
                  >
                    Categoria
                  </label>
                  <select
                    multiple
                    className="form-control"
                    value={data.icon_category}
                    onChange={(e) =>
                      handleTaxonomyChange(
                        index,
                        'icon_category',
                        Array.from(
                          e.target.selectedOptions,
                          (option) => option.value,
                        ),
                      )
                    }
                  >
                    {taxonomyList.length > 0 &&
                      taxonomyList
                        .filter(({ taxonomy }) => taxonomy === 'icon_category')
                        .map(({ term_id, name }) => (
                          <option key={term_id} value={name}>
                            {name}
                          </option>
                        ))}
                  </select>
                </div>
                <div className="mb-3 d-flex gap-3">
                  <label
                    className="form-label d-inline-block"
                    style={{ width: '100px' }}
                  >
                    Estilo
                  </label>
                  <input
                    type="text"
                    className="form-control"
                    list="icon_style"
                    value={data.icon_style}
                    onChange={(e) =>
                      handleTaxonomyChange(index, 'icon_style', e.target.value)
                    }
                  />
                  <datalist id="icon_style">
                    {taxonomyList.length > 0 &&
                      taxonomyList
                        .filter(({ taxonomy }) => taxonomy === 'icon_style')
                        .map(({ term_id, name }) => (
                          <option key={term_id} value={name}>
                            {name}
                          </option>
                        ))}
                  </datalist>
                </div>
                <div className="mb-3 d-flex gap-3">
                  <label
                    className="form-label d-inline-block"
                    style={{ width: '100px' }}
                  >
                    Tag
                  </label>
                  <select
                    multiple
                    className="form-control"
                    value={data.icon_tag}
                    onChange={(e) =>
                      handleTaxonomyChange(
                        index,
                        'icon_tag',
                        Array.from(
                          e.target.selectedOptions,
                          (option) => option.value,
                        ),
                      )
                    }
                  >
                    {taxonomyList.length > 0 &&
                      taxonomyList
                        .filter(({ taxonomy }) => taxonomy === 'icon_tag')
                        .map(({ term_id, name }) => (
                          <option key={term_id} value={name}>
                            {name}
                          </option>
                        ))}
                  </select>
                </div>
              </Col>
            </Col>
          ))}
        </Row>
        <button type="submit" className="btn btn-primary">
          Enviar Mídias
        </button>
      </form>
    </div>
  );
};

export default MediaPostTEST;
