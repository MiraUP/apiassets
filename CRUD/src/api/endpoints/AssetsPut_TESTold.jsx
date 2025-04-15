import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const AssetsPutTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [postid, setPostid] = React.useState('');
  const [taxonomy, setTaxonomy] = React.useState('');
  const [assetsData, setAssetsData] = React.useState([]);
  const [asset, setAsset] = React.useState([]);
  const [thumbnail, setThumbnail] = React.useState('');
  const [previews, setPreviews] = React.useState([]);

  function selectPost(index) {
    fetch(`http://miraup.test/json/api/asset/${index}`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setAsset(json.data);
        console.log(json.data);
        setPostid(index);
        return json;
      });
  }

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/asset/`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setAssetsData(json.data);
        return json;
      });

    fetch(`http://miraup.test/json/api/taxonomy?taxonomy=`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setTaxonomy(json.data);
        json.code === 'error' && setError(json.message);
        return json.data;
      });
  }, []);

  const handleCompatibility = (event) => {
    const updatedCompatibility = [...event.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);

    setAsset((prevState) => ({
      ...prevState,
      compatibility: updatedCompatibility, // Atualiza apenas o campo de compatibilidade
    }));

    console.log(asset);
  };
  console.log(asset);

  const handleTag = (event) => {
    const updatedTag = [...event.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);

    setAsset((prevState) => ({
      ...prevState,
      post_tag: updatedTag, // Atualiza apenas o campo de tag
    }));
  };

  const changeEmphasisHandler = (index) => (event) => {
    const updatedEmphasis = [...asset.emphasis]; // Faz uma cópia do array
    updatedEmphasis[index].value = event.target.value; // Atualiza o valor do item específico
    setAsset((prevAsset) => ({
      ...prevAsset,
      emphasis: updatedEmphasis, // Atualiza o estado com a nova lista de emphasis
    }));
  };

  // Função para remover um item da lista de emphasis
  const removeEmphasisHandler = (index, id) => () => {
    const updatedEmphasis = asset.emphasis.filter((_, i) => i !== index); // Filtra o item a ser removido
    setAsset((prevAsset) => ({
      ...prevAsset, // Mantém as outras propriedades de asset
      emphasis: updatedEmphasis, // Atualiza apenas a propriedade emphasis
    }));

    fetch('http://miraup.test/json/api/asset-put', {
      method: 'POST',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        post_id: asset.id,
        delete_emphasis: id,
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

  const addEmphasisHandler = () => {
    const newId = (asset.emphasis.length + 1).toString(); // Gerando um id único
    const newEmphasis = { id: newId, value: '' }; // Novo objeto com id e valor vazio

    setAsset((prevAsset) => ({
      ...prevAsset, // Mantém as outras propriedades de asset
      emphasis: [...prevAsset.emphasis, newEmphasis], // Adiciona o novo objeto à array emphasis
    }));
  };

  const deletePreview = (index) => (event) => {
    fetch(
      `http://miraup.test/json/api/media?asset_id=${postid}&media_id=${index}`,
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

  function handleSubmit(event) {
    event.preventDefault();

    //console.log(JSON.stringify(asset.compatibility));

    const formData = new FormData(); //Necessário para fazer o Fecth com imagem.
    formData.append('post_id', asset.id);
    formData.append('status', asset.status);
    formData.append('title', asset.title);
    formData.append('subtitle', asset.subtitle);
    formData.append('content', asset.post_content);
    formData.append('category', asset.category[0].term_id);
    formData.append('origin', asset.origin[0].name);
    formData.append('developer', asset.developer[0].name);
    formData.append(
      'compatibility',
      JSON.stringify(asset.compatibility.map(({ name }) => name)),
    );
    formData.append(
      'post_tag',
      JSON.stringify(asset.post_tag.map(({ name }) => name)),
    );
    formData.append('emphasis', JSON.stringify(asset.emphasis));
    formData.append('version', asset.version);
    formData.append('font', asset.font);
    formData.append('size_file', asset.size_file);
    formData.append('download', asset.download);

    //console.log(asset.compatibility.map(({ name }) => name));

    if (thumbnail) {
      formData.append('thumbnail', thumbnail);
    }

    for (let i = 0; i < previews.length; i++) {
      formData.append(`previews${[i]}`, previews[i]);
    }

    fetch('http://miraup.test/json/api/asset-put', {
      method: 'POST',
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: formData,
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Erro na requisição: ' + response.statusText);
        }
        return response.json();
      })
      .then((json) => {
        console.log('Resposta da API:', json.message);
        return json;
      });
    // .catch((error) => {
    //   console.error('Erro:', error);
    // });
  }

  return (
    <>
      <h2>ASSETS PUT</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs="4">
            <Form.Select
              value={postid}
              onChange={({ target }) => selectPost(target.value)}
            >
              <option value="" disabled>
                Escolha um post
              </option>
              {assetsData &&
                assetsData.length > 0 &&
                assetsData != Array.isArray(assetsData) &&
                assetsData.map(({ id, slug, title }, index) => (
                  <option key={index} value={slug}>
                    {id} - {title}
                  </option>
                ))}
            </Form.Select>
          </Col>
          <Col xs={4}>
            <label>Title: {asset.title}</label>
            <Form.Control
              type="text"
              placeholder="Title"
              value={asset.title}
              onChange={({ target }) =>
                setAsset({ ...asset, title: target.value })
              }
            />
          </Col>

          <Col xs={4}>
            <label>Subtitle: {asset.subtitle}</label>
            <Form.Control
              type="text"
              placeholder="Subtitle"
              value={asset.subtitle}
              onChange={({ target }) =>
                setAsset({ ...asset, subtitle: target.value })
              }
            />
          </Col>
          <Col xs={4} className="d-flex flex-column gap-3">
            <label> Thumbnail </label>
            {asset.thumbnail && (
              <img
                alt={'Miniatura do ' + asset.title}
                src={asset.thumbnail}
                width="300"
              />
            )}
            <Form.Control
              type="file"
              onChange={({ target }) => setThumbnail(target.files[0])}
            />
          </Col>
          <Col xs={4} className="d-flex flex-column gap-3">
            <label>Previews</label>
            {asset.previews &&
              asset.previews.map(({ id, url }) => (
                <div className="d-flex gap-3" key={id}>
                  <img
                    alt={'Preview do ' + asset.title}
                    src={url}
                    width="200"
                  />
                  <Button variant="danger" onClick={deletePreview(id)}>
                    Excluir - {id}
                  </Button>
                </div>
              ))}
            <Form.Control
              type="file"
              multiple={true}
              onChange={({ target }) => setPreviews(target.files)}
            />
          </Col>
          <Col xs={4}>
            <label>Content: {asset.post_content}</label>
            <Form.Control
              as="textarea"
              placeholder="Content"
              value={asset.post_content}
              onChange={({ target }) =>
                setAsset({ ...asset, post_content: target.value })
              }
            />
          </Col>
          <Col xs="4">
            <label>Status: {asset.status}</label>
            <Form.Select
              value={asset.status}
              onChange={({ target }) =>
                setAsset({ ...asset, status: target.value })
              }
            >
              <option value="publish">Publish</option>
              <option value="future">Future</option>
              <option value="draft">Draft</option>
              <option value="pending">Pending</option>
              <option value="trash">Trash</option>
              <option value="auto-draft">Auto Draft</option>
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>
              Category: {asset.category && asset.category[0].term_id}
            </label>
            <Form.Select
              value={asset.category && asset.category[0].term_id}
              onChange={({ target }) =>
                setAsset({
                  ...asset,
                  category: [{ ...asset.category[0], term_id: target.value }],
                })
              }
            >
              {taxonomy &&
                taxonomy.length > 0 &&
                taxonomy != Array.isArray(taxonomy) &&
                taxonomy
                  .filter(({ taxonomy }) => taxonomy === 'category')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={term_id}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Origin: {asset.origin && asset.origin[0].name}</label>
            <Form.Select
              value={asset.origin && asset.origin[0].name}
              onChange={({ target }) =>
                setAsset({
                  ...asset,
                  origin: [{ ...asset.origin[0], name: target.value }],
                })
              }
            >
              {taxonomy &&
                taxonomy.length > 0 &&
                taxonomy != Array.isArray(taxonomy) &&
                taxonomy
                  .filter(({ taxonomy }) => taxonomy === 'origin')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>
              Developer: {asset.developer && asset.developer[0].name}
            </label>
            <Form.Select
              value={asset.developer && asset.developer[0].name}
              onChange={({ target }) =>
                setAsset({
                  ...asset,
                  developer: [{ ...asset.developer[0], name: target.value }],
                })
              }
            >
              {taxonomy &&
                taxonomy.length > 0 &&
                taxonomy != Array.isArray(taxonomy) &&
                taxonomy
                  .filter(({ taxonomy }) => taxonomy === 'developer')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label
              className="d-flex flex-column gap-3"
              style={{ border: '1px solid #57606e', padding: '10px' }}
            >
              Emphasis:
              {asset.emphasis &&
                asset.emphasis.map(({ value, id }, index) => (
                  <div key={index} className="d-flex gap-3">
                    {index + 1}
                    <Form.Control
                      type="text"
                      name="emphasis"
                      placeholder="Emphasis"
                      value={value}
                      onChange={changeEmphasisHandler(index)}
                    />
                    <Button
                      variant="danger"
                      onClick={removeEmphasisHandler(index, id)}
                    >
                      Excluir
                    </Button>
                  </div>
                ))}
              <Button variant="success" onClick={addEmphasisHandler}>
                Adicionar Emphasis
              </Button>
            </label>
          </Col>
          <Col xs="4">
            <label>Version: {asset.version}</label>
            <Form.Control
              type="text"
              placeholder="Content"
              value={asset.version}
              onChange={({ target }) =>
                setAsset({ ...asset, version: target.value })
              }
            />
          </Col>
          <Col xs="4">
            <label>Compatibility: </label>
            <Form.Select
              value={
                asset.compatibility &&
                asset.compatibility.length > 0 &&
                asset.compatibility != Array.isArray(asset.compatibility) &&
                asset.compatibility.map(({ name }) => name)
              }
              onChange={handleCompatibility}
              multiple={true}
            >
              {taxonomy &&
                taxonomy.length > 0 &&
                taxonomy != Array.isArray(taxonomy) &&
                taxonomy
                  .filter(({ taxonomy }) => taxonomy === 'compatibility')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Tag: </label>
            <Form.Select
              value={
                asset.post_tag &&
                asset.post_tag.length > 0 &&
                asset.post_tag != Array.isArray(asset.post_tag) &&
                asset.post_tag.map(({ name }) => name)
              }
              onChange={handleTag}
              multiple={true}
            >
              {taxonomy &&
                taxonomy.length > 0 &&
                taxonomy != Array.isArray(taxonomy) &&
                taxonomy
                  .filter(({ taxonomy }) => taxonomy === 'post_tag')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs={4}>
            <label>Font: {asset.font}</label>
            <Form.Control
              type="text"
              placeholder="Font"
              value={asset.font}
              onChange={({ target }) =>
                setAsset({ ...asset, font: target.value })
              }
            />
          </Col>
          <Col xs={4}>
            <label>Size File: {asset.size_file}</label>
            <Form.Control
              type="text"
              placeholder="Font"
              value={asset.size_file}
              onChange={({ target }) =>
                setAsset({ ...asset, size_file: target.value })
              }
            />
          </Col>
          <Col xs={4}>
            <label>Download: {asset.download}</label>
            <Form.Control
              type="text"
              placeholder="Download"
              value={asset.download}
              onChange={({ target }) =>
                setAsset({ ...asset, download: target.value })
              }
            />
          </Col>
          <Col xs={4}>
            <Button type="submit" className="w-100">
              Atualizar
            </Button>
          </Col>
        </Row>
      </form>
    </>
  );
};

export default AssetsPutTEST;
