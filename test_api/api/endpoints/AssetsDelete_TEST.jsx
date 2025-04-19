import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const AssetsDeleteTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [postid, setPostid] = React.useState('');
  const [assetsData, setAssetsData] = React.useState([]);

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/asset/`, {
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
        //console.log(json.data);
        return json;
      });
  }, []);

  function handleSubmit(event) {
    event.preventDefault();

    fetch(`http://miraup.test/json/api/v1/asset/${postid}`, {
      method: 'DELETE',
      headers: {
        Authorization: 'Bearer ' + token,
      },
    })
      .then((response) => {
        console.log(response);
        return response.json();
      })
      .then((json) => {
        console.log(json.data);
        json.code === 'error' && setError(json.message);
        return json.data;
      });
  }
  return (
    <>
      <h2>ASSETS Delete</h2>
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
            <Form.Select
              value={postid}
              onChange={({ target }) => setPostid(target.value)}
            >
              <option value="" disabled>
                Escolha um post
              </option>
              {assetsData &&
                assetsData.length > 0 &&
                assetsData != Array.isArray(assetsData) &&
                assetsData.map(({ id, title }, index) => (
                  <option key={index} value={id}>
                    {id} - {title}
                  </option>
                ))}
            </Form.Select>
          </Col>
          <Col xs="4 ">
            <Button type="submit" className="btn btn-danger w-100">
              Deletar
            </Button>
          </Col>
        </Row>
      </form>
    </>
  );
};

export default AssetsDeleteTEST;
