import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const TaxonomysGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [term_id, setTerm_id] = React.useState('');
  const [taxonomy, setTaxonomy] = React.useState('category');

  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [error, setError] = React.useState('');

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/taxonomy?taxonomy=${taxonomy}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setTaxonomyList(json.data);
        setTerm_id(json.data[0].term_id);
        json.code && setError(json.message);
        return json.data;
      });
  }, [taxonomy]);

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/taxonomy?taxonomy=${taxonomy}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        setTaxonomyList(json.data);
        json.code === 'error' && setError(json.message);
        return json.data;
      });
  }, [term_id]);

  function handleSubmit(event) {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/taxonomy`, {
      method: 'DELETE',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        term_id,
        taxonomy,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        console.log(json);
        json.code && setError(json.message);
        return json;
      });
  }

  return (
    <>
      <h2>TAXONOMY DELETE</h2>
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
          <Col xs={4} className="d-flex gap-3">
            <Col xs={5}>
              <label>Select Taxonomy Type</label>
              <Form.Select
                value={taxonomy}
                onChange={({ target }) => setTaxonomy(target.value)}
              >
                <option value="category">Category</option>
                <option value="post_tag">Post Tag</option>
                <option value="developer">Developer</option>
                <option value="origin">Origin</option>
                <option value="compatibility">Compatibility</option>
                <option value="icon_category">Icon Category</option>
                <option value="icon_style">Icon Style</option>
                <option value="icon_tag">Icon Tag</option>
              </Form.Select>
            </Col>
            <Col xs={6}>
              <label>Taxonomy Select</label>
              <Form.Select
                value={term_id}
                onChange={({ target }) => setTerm_id(target.value)}
              >
                {taxonomyList &&
                  taxonomyList.length > 0 &&
                  taxonomyList != Array.isArray(taxonomyList) &&
                  taxonomyList.map(({ term_id, name }) => (
                    <option key={term_id} value={term_id}>
                      {name}
                    </option>
                  ))}
              </Form.Select>
            </Col>
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="w-100">
              Deletar
            </Button>
          </Col>
        </Row>
        {error && <p>{error}</p>}
      </form>
    </>
  );
};

export default TaxonomysGetTEST;
