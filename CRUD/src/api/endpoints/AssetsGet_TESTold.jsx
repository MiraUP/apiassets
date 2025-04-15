import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';
import AssetsCommentTEST from './AssetsComment_TEST';

const AssetsGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [assetId, setAssetId] = React.useState('');
  const [assetsData, setAssetsData] = React.useState([]);
  // Estado para armazenar o favorito de cada postagem
  const [favorites, setFavorites] = React.useState({});

  function handleSubmit(event) {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/asset/${assetId}/`, {
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
        setAssetsData(json.data);
        console.log(json.data);
        // Atualiza os favoritos para todas as postagens
        if (json.data && Array.isArray(json.data)) {
          const newFavorites = {};
          json.data.forEach((asset) => {
            newFavorites[asset.id] = asset.favorite === true; // Converte "1"/"0" para boolean
          });
          setFavorites(newFavorites);
        }

        return json.data;
      });
  }

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
      <h2>ASSETS GET</h2>
      <Row>
        <Col xs={6}>
          <form onSubmit={handleSubmit}>
            <Row className="flex-column gap-3">
              <Col>
                <Form.Control
                  type="text"
                  placeholder="Token"
                  value={token}
                  onChange={({ target }) => setToken(target.value)}
                />
              </Col>
              <Col>
                <Form.Control
                  type="text"
                  placeholder="ID do Post"
                  value={assetId}
                  onChange={({ target }) => setAssetId(target.value)}
                />
              </Col>
              <Col>
                <Button type="submit" className="w-100">
                  Buscar
                </Button>
              </Col>
            </Row>
          </form>
          <Row>
            {assetsData &&
              assetsData.length > 0 &&
              //assetsData != Array.isArray(assetsData) &&
              assetsData.map(
                ({
                  id,
                  title,
                  status,
                  subtitle,
                  thumbnail,
                  previews,
                  author,
                  category,
                  compatibility,
                  date_create,
                  developer,
                  download,
                  emphasis,
                  entry,
                  font,
                  origin,
                  post_content,
                  post_tag,
                  size_file,
                  update,
                  version,
                  slug,
                  total_comments,
                }) => (
                  <Col key={id} style={{ marginTop: '30px' }}>
                    <hgroup className="d-flex gap-5 ">
                      <h3>
                        {id} | {title}
                      </h3>
                      <Form.Check
                        type="switch"
                        id={id + '-favorite'}
                        checked={favorites[id] || false} // Usa o estado individual de favoritos
                        onChange={() => handleFavorite(id, favorites[id])}
                        label="Favorito"
                      />
                    </hgroup>
                    {thumbnail && (
                      <img src={thumbnail} style={{ width: '300px' }} />
                    )}
                    {slug && (
                      <p>
                        <b>Slug:</b> {slug}
                      </p>
                    )}
                    {status && (
                      <p>
                        <b>Status:</b> {status}
                      </p>
                    )}
                    {subtitle && (
                      <p>
                        <b>Subtitle:</b> {subtitle}
                      </p>
                    )}
                    {previews && (
                      <Col>
                        <p>
                          <b>Previews:</b>
                        </p>
                        <Col className="d-flex">
                          {previews &&
                            previews.map(
                              ({
                                id,
                                title,
                                url,
                                icon_category,
                                icon_styles,
                                icon_tag,
                              }) => (
                                <Col key={id}>
                                  <img
                                    key={id}
                                    src={url}
                                    alt={title}
                                    style={{ width: '100px' }}
                                  />
                                  <p>ID: {id}</p>
                                  <p>Title: {title}</p>
                                  {icon_styles && (
                                    <p>Style: {icon_styles[0].name}</p>
                                  )}
                                  <p>
                                    Categorys:
                                    <br />
                                    {icon_category &&
                                      icon_category.map(
                                        ({ name, slug, term_id }) => (
                                          <span key={term_id}>
                                            {name} - {slug} |
                                          </span>
                                        ),
                                      )}
                                  </p>
                                  <p>
                                    Tags:
                                    <br />
                                    {icon_tag &&
                                      icon_tag.map(
                                        ({ name, slug, term_id }) => (
                                          <span key={term_id}>
                                            {' '}
                                            {name} - {slug} |
                                          </span>
                                        ),
                                      )}
                                  </p>
                                </Col>
                              ),
                            )}
                        </Col>
                      </Col>
                    )}
                    {author && (
                      <p>
                        <b>Author:</b> {author}
                      </p>
                    )}
                    {category && (
                      <p>
                        <b>Category:</b> {category[0].name}
                      </p>
                    )}
                    {compatibility && (
                      <p>
                        <b>Compatibility:</b>{' '}
                        {compatibility.map(({ name, term_id }) => (
                          <span key={term_id}> {name},</span>
                        ))}
                      </p>
                    )}
                    {date_create && (
                      <p>
                        <b>Date Create:</b> {date_create}
                      </p>
                    )}
                    {developer && (
                      <p>
                        <b>Developer:</b> {developer[0].name}
                      </p>
                    )}
                    {download && (
                      <p>
                        <b>Download:</b> {download}
                      </p>
                    )}
                    {emphasis && (
                      <p>
                        <b>Emphasis:</b>{' '}
                        {emphasis.map(({ id, value }) => (
                          <span key={id}>
                            <br />
                            {value}
                          </span>
                        ))}
                      </p>
                    )}
                    {entry && (
                      <p>
                        <b>Entry:</b> {entry}
                      </p>
                    )}
                    {font && (
                      <p>
                        <b>Font:</b> {font}
                      </p>
                    )}
                    {origin && (
                      <p>
                        <b>Origin:</b> {origin[0].name}
                      </p>
                    )}
                    {post_content && (
                      <p>
                        <b>Content:</b> {post_content}
                      </p>
                    )}
                    {post_tag && (
                      <p>
                        <b>Tag:</b>
                        {post_tag.map(({ term_id, name }) => (
                          <span key={term_id}> {name},</span>
                        ))}
                      </p>
                    )}
                    {size_file && (
                      <p>
                        <b>Size File:</b> {size_file}
                      </p>
                    )}
                    {update && (
                      <p>
                        <b>Update:</b> {update}
                      </p>
                    )}
                    {version && (
                      <p>
                        <b>Version:</b> {version}
                      </p>
                    )}
                    {total_comments && (
                      <p>
                        <b>Total Comments:</b> {total_comments}
                      </p>
                    )}
                    {assetId && (
                      <Col style={{ marginTop: '30px' }}>
                        <AssetsCommentTEST assetId={id} />
                      </Col>
                    )}
                  </Col>
                ),
              )}
          </Row>
        </Col>
      </Row>
    </>
  );
};

export default AssetsGetTEST;
