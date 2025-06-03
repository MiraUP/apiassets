import React from 'react';
import { Button, Col, Row, Card } from 'react-bootstrap';
import APIPageCommentGet from '../endpoints/CommentGet_TEST';
import APIPageCommentPost from '../endpoints/CommentPost_TEST';

const APIPageComments = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [assetSlug, setAssetSlug] = React.useState('');
  const [assetsData, setAssetsData] = React.useState([]);
  const [singleAssetData, setSingleAssetData] = React.useState(null);
  const [newComment, setNewComment] = React.useState('');

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/asset/${assetSlug}/`, {
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
        }
      })
      .catch((error) => console.error('Erro:', error));
  }, [assetSlug]);

  return (
    <>
      {assetSlug === '' ? (
        <h2>LISTA DE ATIVOS</h2>
      ) : (
        <h2>
          <Button onClick={() => setAssetSlug('')} size="sm">
            Voltar
          </Button>{' '}
          {singleAssetData && singleAssetData.title}
        </h2>
      )}
      <Row>
        {assetSlug === '' &&
          assetsData.length > 0 &&
          assetsData.map((asset) => (
            <Col key={asset.id}>
              <Card onClick={() => setAssetSlug(asset.slug)}>
                <Card.Img variant="top" src={asset.thumbnail} />
                <Card.Body>
                  <Card.Title>{asset.title}</Card.Title>
                </Card.Body>
              </Card>
            </Col>
          ))}
        {assetSlug !== '' && singleAssetData && (
          <Col>
            <Card>
              <Card.Header
                style={{ padding: '0', overflow: 'hidden', height: '200px' }}
              >
                <Card.Img variant="top" src={singleAssetData.thumbnail} />
              </Card.Header>
              <Card.Body>
                <br />
                <Card.Title>Comentários</Card.Title>
                <br />
                <APIPageCommentGet
                  PostID={singleAssetData.id}
                  newComment={setNewComment}
                />
                <APIPageCommentPost PostID={singleAssetData.id} />
              </Card.Body>
            </Card>
          </Col>
        )}
      </Row>
    </>
  );
};

export default APIPageComments;
