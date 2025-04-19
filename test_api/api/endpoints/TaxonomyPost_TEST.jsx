import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const TaxonomysPostTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [taxonomy, setTaxonomy] = React.useState('category');
  const [name, setName] = React.useState('');
  const [slug, setSlug] = React.useState('');
  const [description, setDescription] = React.useState('');
  const [error, setError] = React.useState('');

  function handleSubmit(event) {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/v1/taxonomy/`, {
      method: 'POST',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        taxonomy,
        name,
        slug,
        description,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        json.code === 'error' && setError(json.message);
        return json.data;
      });
  }

  return (
    <>
      <h2>TAXONOMY POST</h2>
      <form onSubmit={handleSubmit}>
        <Row className="flex-column gap-3">
          <Col xs={4}>
            <Form.Control
              type="text"
              placeholder="Token"
              value={token}
              onChange={({ target }) => setToken(target.value)}
            />
          </Col>
          <Col xs={4}>
            <label>Taxonomy Select Post</label>
            <Form.Select
              value={taxonomy}
              onChange={({ target }) => setTaxonomy(target.value)}
            >
              <option value="category">Category</option>
              <option value="post_tag">Post Tag</option>
              <option value="developer">Developer</option>
              <option value="origin">Origin</option>
              <option value="compatibility">Compatibility</option>{' '}
              <option value="icon_category">Icon Category</option>
              <option value="icon_style">Icon Style</option>
              <option value="icon_tag">Icon Tag</option>
            </Form.Select>
          </Col>
          <Col xs={4}>
            <Form.Control
              type="text"
              placeholder="Name"
              value={name}
              onChange={({ target }) => setName(target.value)}
            />
          </Col>
          <Col xs={4}>
            <Form.Control
              type="text"
              placeholder="Slug"
              value={slug}
              onChange={({ target }) => setSlug(target.value)}
            />
          </Col>
          <Col xs={4}>
            <Form.Control
              type="text"
              placeholder="Description"
              value={description}
              onChange={({ target }) => setDescription(target.value)}
            />
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="w-100">
              Cadastrar
            </Button>
          </Col>
        </Row>
        {error && <p>{error}</p>}
      </form>
    </>
  );
};

export default TaxonomysPostTEST;
