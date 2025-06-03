import React from 'react';
import { Table, Col, Form, Row, Alert } from 'react-bootstrap';

const TaxonomysGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [taxonomy, setTaxonomy] = React.useState('category');
  const [taxonomyList, setTaxonomyList] = React.useState([]);
  const [message, setMessage] = React.useState({ type: '', text: '' });

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/taxonomy?taxonomy=${taxonomy}`, {
      method: 'GET',
      headers: {
        Authorization: 'Bearer ' + token,
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
  }, [taxonomy]);

  return (
    <>
      <h2>TAXONOMY GET</h2>
      {message.text && <Alert variant={message.type}>{message.text}</Alert>}
      <Row className="gap-3">
        <Col xs={4}>
          <label>Taxonomy select</label>
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
            <option value="notification">Notification</option>
          </Form.Select>
        </Col>
        <Col xs="auto">
          {taxonomyList && taxonomyList.length > 0 && (
            <Table striped bordered hover>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nome</th>
                  <th>Slug</th>
                  <th>Descrição</th>
                </tr>
              </thead>
              <tbody>
                {taxonomyList != Array.isArray(taxonomyList) &&
                  taxonomyList.map(({ term_id, name, slug, description }) => (
                    <tr key={term_id}>
                      <td>{term_id}</td>
                      <td>{name}</td>
                      <td>{slug}</td>
                      <td>{description}</td>
                    </tr>
                  ))}
              </tbody>
            </Table>
          )}
        </Col>
      </Row>
    </>
  );
};

export default TaxonomysGetTEST;
