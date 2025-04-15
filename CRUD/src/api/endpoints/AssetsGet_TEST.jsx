import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';
import AssetsCommentTEST from './AssetsComment_TEST';

const AssetsGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [assetSlug, setAssetSlug] = React.useState('');
  const [assetsData, setAssetsData] = React.useState([]);
  const [singleAssetData, setSingleAssetData] = React.useState(null);
  const [favorites, setFavorites] = React.useState({});

  // Estados para os filtros
  const [dateCreated, setDateCreated] = React.useState('DESC');
  const [dateModified, setDateModified] = React.useState('DESC');
  const [author, setAuthor] = React.useState('');
  const [category, setCategory] = React.useState('');
  const [tags, setTags] = React.useState('');
  const [compatibility, setCompatibility] = React.useState('');
  const [developer, setDeveloper] = React.useState('');
  const [origin, setOrigin] = React.useState('');
  const [favorite, setFavorite] = React.useState('');
  const [newPosts, setNewPosts] = React.useState(false);
  const [page, setPage] = React.useState(1);
  const [totalPage, setTotalPage] = React.useState();
  const [statistics, setStatistics] = React.useState('');
  const [postId, setPostId] = React.useState('');

  // Constrói a URL com os filtros
  const params = new URLSearchParams({
    date_created: dateCreated,
    date_modified: dateModified,
    user: author,
    category,
    tags,
    compatibility,
    developer,
    origin,
    favorite,
    new: newPosts,
    page,
  });

  function handleSubmit(event) {
    event.preventDefault();

    const url = assetSlug
      ? `http://miraup.test/json/api/asset/${assetSlug}/`
      : `http://miraup.test/json/api/asset/?${params.toString()}`;

    fetch(url, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        if (assetSlug) {
          setSingleAssetData(json.data);
          setAssetsData([]);
        } else {
          setAssetsData(json.data);
          setSingleAssetData(null);

          if (json.data && Array.isArray(json.data)) {
            const newFavorites = {};
            json.data.forEach((asset) => {
              newFavorites[asset.id] = asset.favorite === true;
            });
            setFavorites(newFavorites);
          }
        }
      })
      .catch((error) => console.error('Erro:', error));
  }

  React.useEffect(() => {
    const url = assetSlug
      ? `http://miraup.test/json/api/asset/${assetSlug}/`
      : `http://miraup.test/json/api/asset/?${params.toString()}`;

    fetch(url, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => response.json())
      .then((json) => {
        if (assetSlug) {
          setSingleAssetData(json.data);
          setAssetsData([]);
        } else {
          setAssetsData(json.data);
          setSingleAssetData(null);
          setTotalPage(json.total_pages);

          if (json.data && Array.isArray(json.data)) {
            const newFavorites = {};
            json.data.forEach((asset) => {
              newFavorites[asset.id] = asset.favorite === true;
            });
            setFavorites(newFavorites);
          }
        }
      })
      .catch((error) => console.error('Erro:', error));
  }, [assetSlug, page]);

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
        //console.log('Resposta da API:', json);

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

  React.useEffect(() => {
    statistics &&
      fetch(
        `http://miraup.test/json/api/statistics?post_id=${postId}&&action_type=${statistics}`,
        {
          method: 'POST',
          headers: {
            Authorization: 'Bearer ' + token,
          },
        },
      )
        .then((response) => response.json())
        .then((json) => {
          console.log('Statistics:', json.data);
          setStatistics('');
          setPostId('');
        })
        .catch((error) => console.error('Erro:', error));
  }, [statistics]);
  return (
    <>
      <h2>ASSETS GET</h2>
      <Row>
        <Col>
          <form onSubmit={handleSubmit}>
            <Row className="gap-3">
              {/* Filtros */}
              <Col xs={2}>
                <Form.Select
                  value={dateCreated}
                  onChange={({ target }) => setDateCreated(target.value)}
                >
                  <option value="">Mais Recente</option>
                  <option value="ASC">Mais Antiga</option>
                </Form.Select>
              </Col>
              <Col xs={2}>
                <Form.Select
                  value={dateModified}
                  onChange={({ target }) => setDateModified(target.value)}
                >
                  <option value="">Data de Modificação (Mais Recente)</option>
                  <option value="ASC">Data de Modificação (Mais Antiga)</option>
                </Form.Select>
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Autor (ID)"
                  value={author}
                  onChange={({ target }) => setAuthor(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Categoria (ID)"
                  value={category}
                  onChange={({ target }) => setCategory(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Tags"
                  value={tags}
                  onChange={({ target }) => setTags(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Compatibilidade"
                  value={compatibility}
                  onChange={({ target }) => setCompatibility(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Developer"
                  value={developer}
                  onChange={({ target }) => setDeveloper(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Control
                  type="text"
                  placeholder="Origin"
                  value={origin}
                  onChange={({ target }) => setOrigin(target.value)}
                />
              </Col>
              <Col xs={2}>
                <Form.Check
                  type="switch"
                  label="Favoritos"
                  checked={favorite === 'true'}
                  onChange={({ target }) =>
                    setFavorite(target.checked ? 'true' : 'false')
                  }
                />
              </Col>
              <Col xs={2}>
                <Form.Check
                  type="switch"
                  label="Novos (últimos 3 meses)"
                  checked={newPosts}
                  onChange={({ target }) => setNewPosts(target.checked)}
                />
              </Col>
              <Col xs={12}>
                <Button type="submit" className="w-100">
                  Buscar
                </Button>
              </Col>
            </Row>
          </form>

          <Row>
            {/* Exibe o post específico ou a lista de posts */}
            {singleAssetData ? (
              <Col style={{ marginTop: '30px' }}>
                <Row>
                  <Col xs={3}>
                    <Button onClick={() => setAssetSlug('')}>Voltar</Button>
                    <hgroup className="d-flex gap-5 ">
                      <h3>
                        {singleAssetData.id} | {singleAssetData.title}
                      </h3>
                      <Form.Check
                        type="switch"
                        id={singleAssetData.id + '-favorite'}
                        checked={favorites[singleAssetData.id] || false} // Usa o estado individual de favoritos
                        onChange={() =>
                          handleFavorite(
                            singleAssetData.id,
                            favorites[singleAssetData.id],
                          )
                        }
                        label="Favorito"
                      />
                    </hgroup>
                    <p>SubTitle: {singleAssetData.subtitle}</p>
                    <img
                      src={singleAssetData.thumbnail}
                      style={{ width: '300px' }}
                    />
                  </Col>
                  <Col xs={3}>
                    <p>
                      <b>Content:</b> {singleAssetData.post_content}
                    </p>
                    {singleAssetData.previews && (
                      <Col>
                        <p>
                          <b>Previews:</b>
                        </p>
                        <Col className="d-flex">
                          {console.log(singleAssetData)}
                          {singleAssetData.previews &&
                            singleAssetData.previews.length > 0 &&
                            singleAssetData.previews.map(
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
                  </Col>
                  <Col xs={3}>
                    <p>
                      <b>Slug:</b> {singleAssetData.slug}
                    </p>
                    <p>
                      <b>Status:</b> {singleAssetData.status}
                    </p>

                    <p>
                      <b>Author:</b> {singleAssetData.author}
                    </p>

                    <p>
                      <b>Category:</b> {singleAssetData.category[0].name}
                    </p>

                    <p>
                      <b>Compatibility:</b>{' '}
                      {singleAssetData.compatibility.map(
                        ({ name, term_id }) => (
                          <span key={term_id}> {name},</span>
                        ),
                      )}
                    </p>

                    <p>
                      <b>Date Create:</b> {singleAssetData.date_create}
                    </p>

                    <p>
                      <b>Developer:</b> {singleAssetData.developer[0].name}
                    </p>

                    <p>
                      <b>Emphasis:</b>
                      <ul>
                        {singleAssetData.emphasis
                          ? singleAssetData.emphasis.map(({ id, value }) => (
                              <li key={id}>{value}</li>
                            ))
                          : ' Nenhum destaque'}
                      </ul>
                    </p>
                    <p>
                      <b>Entry:</b> {singleAssetData.entry}
                    </p>
                    <p>
                      <b>Font:</b> {singleAssetData.font}
                    </p>
                    <p>
                      <b>Origin:</b> {singleAssetData.origin[0].name}
                    </p>
                    <p>
                      <b>Download:</b>{' '}
                      <Button
                        as="a"
                        link={singleAssetData.download}
                        onClick={() =>
                          setStatistics('download') +
                          setPostId(singleAssetData.id)
                        }
                      >
                        Download
                      </Button>
                    </p>
                  </Col>
                  <Col xs={3}>
                    {singleAssetData.post_tag && (
                      <p>
                        <b>Tag:</b>
                        {singleAssetData.post_tag.map(({ term_id, name }) => (
                          <span key={term_id}> {name},</span>
                        ))}
                      </p>
                    )}
                    <p>
                      <b>Size File:</b> {singleAssetData.size_file}
                    </p>
                    <p>
                      <b>Update:</b> {singleAssetData.update}
                    </p>
                    <p>
                      <b>Version:</b> {singleAssetData.version}
                    </p>
                    <p>
                      <b>Total Comments:</b> {singleAssetData.total_comments}
                    </p>
                    <Col style={{ marginTop: '30px' }}>
                      <AssetsCommentTEST assetId={singleAssetData.id} />
                    </Col>
                  </Col>
                </Row>
              </Col>
            ) : (
              assetsData.map((asset) => (
                <Col xs={3} key={asset.id} style={{ marginTop: '30px' }}>
                  <hgroup className="d-flex gap-5 ">
                    <h3>
                      {asset.id} | {asset.title}
                    </h3>
                    <Form.Check
                      type="switch"
                      id={asset.id + '-favorite'}
                      checked={favorites[asset.id] || false} // Usa o estado individual de favoritos
                      onChange={() =>
                        handleFavorite(asset.id, favorites[asset.id])
                      }
                      label="Favorito"
                    />
                  </hgroup>
                  <p>{asset.slug}</p>
                  <p>Criado: {asset.date_create}</p>
                  <img
                    src={asset.thumbnail}
                    style={{ width: '300px' }}
                    onClick={() =>
                      setAssetSlug(asset.slug) +
                      setStatistics('view') +
                      setPostId(asset.id)
                    }
                  />
                </Col>
              ))
            )}
            <p>Total pages {totalPage}</p>
            <p>Page {page}</p>
            <div>
              <Button
                onClick={() => setPage(page - 1)}
                disabled={page <= 1}
                style={{ marginRight: '10px' }}
              >
                Anterior
              </Button>
              <Button
                onClick={() => setPage(page + 1)}
                disabled={page >= totalPage}
              >
                Próximo
              </Button>
            </div>
          </Row>
        </Col>
      </Row>
    </>
  );
};

export default AssetsGetTEST;
