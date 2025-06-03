import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const AssetsPostTEST = () => {
  const [emphasis, setEmphasis] = React.useState([{ emphasis: '' }]);
  const handleAddInput = () => {
    setEmphasis([...emphasis, { emphasis: '' }]);
  };
  const handleChange = (event, index) => {
    let { name, value } = event.target;
    let onChangeValue = [...emphasis];
    onChangeValue[index][name] = value;
    setEmphasis(onChangeValue);
  };
  const handleDeleteInput = (index) => {
    const newArray = [...emphasis];
    newArray.splice(index, 1);
    setEmphasis(newArray);
  };

  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [title, setTitle] = React.useState('');
  const [subtitle, setSubtitle] = React.useState('');
  const [content, setContent] = React.useState('');
  const [thumbnail, setThumbnail] = React.useState('');
  const [previews, setPreviews] = React.useState([]);
  const [category, setCategory] = React.useState([]);
  const [compatibility, setCompatibility] = React.useState([]);
  const [developer, setDeveloper] = React.useState([]);
  const [origin, setOrigin] = React.useState([]);
  const [tag, setTag] = React.useState([]);
  const [icon_style, setIconStyle] = React.useState([]);
  const [version, setVersion] = React.useState('');
  const [font, setFont] = React.useState('');
  const [size_file, setSizeFile] = React.useState('');
  const [download, setDownload] = React.useState('');
  const [error, setError] = React.useState('');

  const [taxonomyName, setTaxonomyName] = React.useState('');
  const [taxonomyList, setTaxonomyList] = React.useState([]);

  function handleSubmit(event) {
    event.preventDefault();

    setError('');

    const formData = new FormData(); //Necessário para fazer o Fecth com imagem.
    formData.append('title', title);
    formData.append('subtitle', subtitle);
    formData.append('content', content);
    formData.append('thumbnail', thumbnail);
    formData.append('version', version);
    formData.append('category', JSON.stringify(category));
    formData.append('developer', JSON.stringify(developer));
    formData.append('origin', JSON.stringify(origin));
    formData.append('tag', JSON.stringify(tag));
    formData.append('compatibility', JSON.stringify(compatibility));
    formData.append('icon_style', JSON.stringify(icon_style));
    formData.append('emphasis', JSON.stringify(emphasis));
    formData.append('font', font);
    formData.append('size_file', size_file);
    formData.append('download', download);

    for (let i = 0; i < previews.length; i++) {
      formData.append(`previews-${[i]}`, previews[i]);
    }

    fetch('http://miraup.test/json/api/v1/asset', {
      method: 'POST',
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: formData,
    })
      .then((response) => {
        //console.log(response);
        return response.json();
      })
      .then((json) => {
        json.code && setError(json.message);
        console.log(json);
        return json;
      });
  }

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/taxonomy?taxonomy=${taxonomyName}`, {
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

  const handleCompatibily = (e) => {
    const updatedOptions = [...e.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);
    setCompatibility(updatedOptions);
  };

  const handleTag = (e) => {
    const updatedOptions = [...e.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);
    setTag(updatedOptions);
  };

  const handleIconStyle = (e) => {
    const updatedOptions = [...e.target.options]
      .filter((option) => option.selected)
      .map((x) => x.value);
    setIconStyle(updatedOptions);
  };

  return (
    <>
      <h2>ASSETS POST</h2>
      <div className="body"> {JSON.stringify(emphasis)} </div>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Token"
              value={token}
              onChange={({ target }) => setToken(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Title"
              value={title}
              onChange={({ target }) => setTitle(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Subtitle"
              value={subtitle}
              onChange={({ target }) => setSubtitle(target.value)}
            />
          </Col>
          <Col xs="4">
            <label>
              Thumbnail
              <Form.Control
                type="file"
                onChange={({ target }) => setThumbnail(target.files[0])}
              />
            </label>
          </Col>
          <Col xs="4">
            <label>
              Previews
              <Form.Control
                type="file"
                multiple={true}
                onChange={({ target }) => setPreviews(target.files)}
              />
            </label>
          </Col>
          <Col xs="4">
            <Form.Control
              as="textarea"
              placeholder="Content"
              value={content}
              onChange={({ target }) => setContent(target.value)}
            />
          </Col>
          <Col xs="4">
            <label>Category: {category}</label>
            <Form.Select
              value={category}
              onChange={({ target }) => setCategory(target.value)}
            >
              {taxonomyList &&
                taxonomyList.length > 0 &&
                taxonomyList != Array.isArray(taxonomyList) &&
                taxonomyList
                  .filter(({ taxonomy }) => taxonomy === 'category')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Developer: {developer}</label>
            <Form.Select
              value={developer}
              onChange={({ target }) => setDeveloper(target.value)}
            >
              {taxonomyList &&
                taxonomyList.length > 0 &&
                taxonomyList != Array.isArray(taxonomyList) &&
                taxonomyList
                  .filter(({ taxonomy }) => taxonomy === 'developer')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Origin</label>
            <Form.Select
              value={origin}
              onChange={({ target }) => setOrigin(target.value)}
            >
              {taxonomyList &&
                taxonomyList.length > 0 &&
                taxonomyList != Array.isArray(taxonomyList) &&
                taxonomyList
                  .filter(({ taxonomy }) => taxonomy === 'origin')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Compatibility</label>
            <Form.Select
              value={compatibility}
              onChange={handleCompatibily}
              multiple={true}
            >
              {taxonomyList &&
                taxonomyList.length > 0 &&
                taxonomyList != Array.isArray(taxonomyList) &&
                taxonomyList
                  .filter(({ taxonomy }) => taxonomy === 'compatibility')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Tags</label>

            <Form.Select value={tag} onChange={handleTag} multiple={true}>
              {taxonomyList &&
                taxonomyList.length > 0 &&
                taxonomyList != Array.isArray(taxonomyList) &&
                taxonomyList
                  .filter(({ taxonomy }) => taxonomy === 'post_tag')
                  .map(({ term_id, name }) => (
                    <option key={term_id} value={name}>
                      {name}
                    </option>
                  ))}
            </Form.Select>
          </Col>
          <Col xs="4">
            <label>Icon Style</label>
            <Form.Select
              value={icon_style}
              onChange={handleIconStyle}
              multiple={true}
            >
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
            </Form.Select>
          </Col>
          <Col xs="4">
            {emphasis.map((item, index) => (
              <div key={index} className="d-flex gap-3">
                <Form.Control
                  type="text"
                  name="emphasis"
                  placeholder="Emphasis"
                  value={item.emphasis}
                  onChange={(event) => handleChange(event, index)}
                />
                {emphasis.length > 1 && (
                  <Button onClick={() => handleDeleteInput(index)}>
                    Delete
                  </Button>
                )}
                {index === emphasis.length - 1 && (
                  <Button onClick={() => handleAddInput()}>+</Button>
                )}
              </div>
            ))}
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Font"
              value={font}
              onChange={({ target }) => setFont(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Size File"
              value={size_file}
              onChange={({ target }) => setSizeFile(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Version"
              value={version}
              onChange={({ target }) => setVersion(target.value)}
            />
          </Col>
          <Col xs="4">
            <Form.Control
              type="text"
              placeholder="Download"
              value={download}
              onChange={({ target }) => setDownload(target.value)}
            />
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="w-100">
              Cadastrar
            </Button>
          </Col>
        </Row>
        <Row>
          <Col>{error && <p>{error}</p>}</Col>
        </Row>
      </form>
    </>
  );
};

export default AssetsPostTEST;
