import React from 'react';
import { Button, Col, Form, Row, Card } from 'react-bootstrap';

const MediaPutTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [assetsData, setAssetsData] = React.useState([]);
  const [postid, setPostid] = React.useState('');
  const [selectMedia, setSelectMedia] = React.useState('');
  const [taxonomyName, setTaxonomyName] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [asset, setAsset] = React.useState(null);
  const [category, setCategory] = React.useState([]);
  const [style, setStyle] = React.useState([]);
  const [newTag, setNewTag] = React.useState('');
  const [deleteTag, setDeleteTag] = React.useState('');
  const [deleteMedia, setDeleteMedia] = React.useState('');
  const [message, setMessage] = React.useState({ type: '', text: '' }); // Mensagem de sucesso ou erro

  // Carrega dados iniciais
  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/asset/`, {
      method: 'GET',
      headers: { Authorization: 'Bearer ' + token },
    })
      .then((response) => response.json())
      .then((json) => setAssetsData(json.data))
      .catch((error) => console.error('Erro ao carregar assets:', error));
  }, [token]);

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

  // Carrega asset específico
  React.useEffect(() => {
    if (postid) {
      fetch(`http://miraup.test/json/api/asset/${postid}`, {
        method: 'GET',
        headers: { Authorization: 'Bearer ' + token },
      })
        .then((response) => response.json())
        .then((json) => setAsset(json.data))
        .catch((error) => console.error('Erro ao carregar asset:', error));
    }
    setSelectMedia('');
  }, [postid, token]);

  // Função para enviar os dados atualizados
  const handleSubmit = (event) => {
    event.preventDefault();

    if (!asset) {
      console.error('Nenhum asset selecionado.');
      return;
    }

    const dataToUpdate = {
      post_slug: postid,
      icon_id: selectMedia,
      post_category: category,
      post_style: style,
      post_tag: newTag,
      delete_tag: deleteTag,
    };

    fetch('http://miraup.test/json/api/media/', {
      method: 'PUT',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify(dataToUpdate),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => console.log('Resposta da API:', json.data))
      .catch((error) => console.error('Erro:', error));
  };

  const handleCategory = (e) => {
    const updatedOptions = [...e.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);
    setCategory(updatedOptions);
  };

  React.useEffect(() => {
    if (deleteMedia) {
      const dataDelete = {
        post_slug: postid,
        media_id: selectMedia,
      };

      fetch('http://miraup.test/json/api/media/', {
        method: 'DELETE',
        headers: {
          'Content-type': 'application/json',
          Authorization: 'Bearer ' + token,
        },
        body: JSON.stringify(dataDelete),
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.statusText);
          }
          return response.json();
        })
        .then((json) => console.log('Resposta da API:', json.data))
        .catch((error) => console.error('Erro:', error));
    }
  }, [deleteMedia]);

  return (
    <>
      <h2>Update Data Media Icons</h2>
      <Row className="flex-column gap-3">
        <Col xs={4}>
          <Form.Select
            value={postid}
            onChange={({ target }) => setPostid(target.value)}
          >
            <option value="" disabled>
              Escolha um post
            </option>
            {Array.isArray(assetsData) &&
              assetsData.map(({ id, title, category, slug }, index) => (
                <option key={index} value={slug}>
                  {slug} - {title} - {category[0]?.slug}
                </option>
              ))}
          </Form.Select>
        </Col>
        <Col>
          <Row>
            {selectMedia === '' &&
              asset &&
              asset.previews &&
              asset.previews.length > 0 &&
              asset.previews.map((media) => (
                <Col key={media.id} xs={2} className="text-center mb-4">
                  <Card onClick={() => setSelectMedia(media.id)}>
                    <Card.Img
                      variant="top"
                      src={media.url}
                      style={{ maxWidth: '300px', margin: '0 auto' }}
                    />
                    <Card.Title>{media.title}</Card.Title>
                  </Card>
                </Col>
              ))}

            {selectMedia != '' &&
              asset &&
              asset.previews &&
              asset.previews.length > 0 &&
              asset.previews
                .filter((media) => media.id === selectMedia)
                .map((media, index) => (
                  <form key={index} onSubmit={handleSubmit}>
                    <Col key={media.id} xs={12} className="text-center mb-4">
                      <Row>
                        <Col xs={4}>
                          <img src={media.url} />
                        </Col>
                        <Col>
                          <Row>
                            <Col className="d-flex gap-3">
                              <Button
                                className="p-2"
                                onClick={() =>
                                  setSelectMedia('') + setDeleteTag('')
                                }
                              >
                                Voltar
                              </Button>
                              <h3 className="text-start p-2">
                                {media.id} | {media.title}
                              </h3>
                              <Button
                                variant="danger"
                                className="p-2 ms-auto"
                                onClick={() => setDeleteMedia(media.id)}
                              >
                                Excluir
                              </Button>
                            </Col>
                          </Row>
                          <hr />
                          <Row>
                            <Col>
                              <Row>
                                <Col>
                                  <label
                                    className="form-label d-inline-block"
                                    style={{ width: '100px' }}
                                  >
                                    Categoria
                                  </label>
                                  <select
                                    multiple
                                    className="form-control"
                                    value={category}
                                    onChange={handleCategory}
                                  >
                                    {taxonomyList
                                      .filter(
                                        ({ taxonomy }) =>
                                          taxonomy === 'icon_category',
                                      )
                                      .map(({ term_id, name }) => (
                                        <option key={term_id} value={name}>
                                          {name}
                                        </option>
                                      ))}
                                  </select>
                                </Col>
                              </Row>
                              <Row>
                                <Col>
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
                                    value={style}
                                    onChange={(e) => setStyle(e.target.value)}
                                  />
                                  <datalist id="icon_style">
                                    {taxonomyList
                                      .filter(
                                        ({ taxonomy }) =>
                                          taxonomy === 'icon_style',
                                      )
                                      .map(({ term_id, name }) => (
                                        <option key={term_id} value={name}>
                                          {name}
                                        </option>
                                      ))}
                                  </datalist>
                                </Col>
                              </Row>
                            </Col>
                            <Col>
                              <b>Tags</b>
                              <div
                                className="d-flex flex-wrap gap-2"
                                style={{ padding: '10px' }}
                              >
                                <Row className="w-100">
                                  <Col xs={12} className="d-flex gap-3">
                                    <input
                                      type="text"
                                      placeholder="Nova Tag"
                                      className="form-control w-100"
                                      value={newTag}
                                      onChange={(e) =>
                                        setNewTag(e.target.value)
                                      }
                                    />
                                    <Button type="submit">Adicionar</Button>
                                  </Col>
                                </Row>
                                <hr />
                                <Row>
                                  <Col>
                                    {media.icon_tag.length > 0
                                      ? media.icon_tag.map((tag) => (
                                          <span
                                            key={tag.term_id}
                                            className="badge bg-info text-dark d-inline-block rounded-pill"
                                            style={{
                                              padding:
                                                '.1rem .1rem .1rem .5rem',
                                            }}
                                          >
                                            {tag.name}
                                            <Button
                                              size="sm"
                                              variant="danger"
                                              className="rounded-pill"
                                              type="submit"
                                              style={{
                                                padding: '0 0.4rem',
                                                marginLeft: '.1rem',
                                              }}
                                              onClick={() =>
                                                setDeleteTag(tag.name)
                                              }
                                            >
                                              X
                                            </Button>
                                          </span>
                                        ))
                                      : 'Nenhuma tag cadastrada'}
                                  </Col>
                                </Row>
                              </div>
                            </Col>
                          </Row>
                          <hr />
                          <Button
                            type="submit"
                            variant="success"
                            size="lg"
                            className="w-100"
                          >
                            Atualizar
                          </Button>
                        </Col>
                      </Row>
                    </Col>
                  </form>
                ))}
          </Row>
        </Col>
      </Row>
    </>
  );
};

export default MediaPutTEST;
