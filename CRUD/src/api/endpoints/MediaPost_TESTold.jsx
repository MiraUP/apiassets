import React, { useState, useEffect } from 'react';
import 'bootstrap/dist/css/bootstrap.min.css';

const MediaPostTEST = () => {
  const [posts, setPosts] = useState([]); // Lista de posts
  const [selectedPostId, setSelectedPostId] = useState(''); // ID do post selecionado
  const [files, setFiles] = useState([]); // Arquivos selecionados
  const [token, setToken] = useState(''); // Token de autenticação
  const [taxonomies, setTaxonomies] = useState({
    icon_category: [],
    icon_style: [],
    icon_tag: [],
  }); // Lista de taxonomias
  const [fileData, setFileData] = useState([]); // Dados das imagens (preview e taxonomias)

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
  useEffect(() => {
    const fetchTaxonomies = async () => {
      try {
        const categories = await fetch(
          'http://miraup.test/wp-json/wp/v2/icon_category',
        );
        const styles = await fetch(
          'http://miraup.test/wp-json/wp/v2/icon_style',
        );
        const tags = await fetch('http://miraup.test/wp-json/wp/v2/icon_tag');

        if (!categories.ok || !styles.ok || !tags.ok) {
          throw new Error('Erro ao buscar taxonomias');
        }

        const categoriesData = await categories.json();
        const stylesData = await styles.json();
        const tagsData = await tags.json();

        setTaxonomies({
          icon_category: categoriesData,
          icon_style: stylesData,
          icon_tag: tagsData,
        });
      } catch (error) {
        console.error('Erro:', error);
      }
    };

    fetchTaxonomies();
  }, []);

  // Atualiza os dados das imagens quando novos arquivos são selecionados
  useEffect(() => {
    if (files.length > 0) {
      const newFileData = files.map((file) => ({
        file,
        preview: URL.createObjectURL(file),
        icon_category: '',
        icon_style: '',
        icon_tag: '',
      }));
      setFileData(newFileData);
    }
  }, [files]);

  // Manipula o envio do formulário
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!selectedPostId || files.length === 0) {
      console.log('Selecione um post e pelo menos uma imagem.');
      return;
    }

    const formData = new FormData();
    formData.append('post_id', selectedPostId);

    fileData.forEach((data, index) => {
      formData.append(`preview[${index}]`, data.file);
      formData.append(`icon_category[${index}]`, data.icon_category);
      formData.append(`icon_style[${index}]`, data.icon_style);
      formData.append(`icon_tag[${index}]`, data.icon_tag);
    });

    try {
      const response = await fetch('http://miraup.test/json/api/media', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
        },
        body: formData,
      });

      if (!response.ok) {
        throw new Error('Erro ao enviar mídias');
      }

      const result = await response.json();
      console.log(result);
    } catch (error) {
      console.error('Erro:', error);
      console.log('Falha ao enviar mídias.');
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

        {/* Preview das Imagens e Inputs de Taxonomias */}
        {fileData.map((data, index) => (
          <div key={index} className="mb-4 p-3 border rounded">
            <img
              src={data.preview}
              alt={`Preview ${index}`}
              className="img-thumbnail mb-3"
              style={{ maxWidth: '200px', maxHeight: '200px' }}
            />
            <div className="mb-3">
              <label className="form-label">Categoria</label>
              <input
                type="text"
                className="form-control"
                list="icon_category"
                value={data.icon_category}
                onChange={(e) =>
                  handleTaxonomyChange(index, 'icon_category', e.target.value)
                }
              />
              <datalist id="icon_category">
                {taxonomies.icon_category.map((category) => (
                  <option key={category.id} value={category.name} />
                ))}
              </datalist>
            </div>
            <div className="mb-3">
              <label className="form-label">Estilo</label>
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
                {taxonomies.icon_style.map((style) => (
                  <option key={style.id} value={style.name} />
                ))}
              </datalist>
            </div>
            <div className="mb-3">
              <label className="form-label">Tag</label>
              <input
                type="text"
                className="form-control"
                list="icon_tag"
                value={data.icon_tag}
                onChange={(e) =>
                  handleTaxonomyChange(index, 'icon_tag', e.target.value)
                }
              />
              <datalist id="icon_tag">
                {taxonomies.icon_tag.map((tag) => (
                  <option key={tag.id} value={tag.name} />
                ))}
              </datalist>
            </div>
          </div>
        ))}

        <button type="submit" className="btn btn-primary">
          Enviar Mídias
        </button>
      </form>
    </div>
  );
};

export default MediaPostTEST;
