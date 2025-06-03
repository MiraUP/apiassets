import React from 'react';
import { Button, Col, Form, Row } from 'react-bootstrap';

const NotificationGetTEST = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');
  const [notifications, setNotifications] = React.useState([]);
  const [reads, setRead] = React.useState({});

  React.useEffect(() => {
    fetch(`http://miraup.test/json/api/v1/notifications`, {
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
        console.log(json.data);
        if (json.success && Array.isArray(json.data)) {
          setNotifications(json.data);
          // Inicializa o estado de leitura
          const newRead = {};
          json.data.forEach((notification) => {
            newRead[notification.id] = notification.read || false; // Define como false se não existir
          });
          setRead(newRead);
        }

        return json;
      });
  }, []);

  console.log(notifications);

  const handleRead = (id, currentRead) => {
    const newRead = !currentRead; // Inverte o estado de leitura

    // Atualiza o estado local imediatamente para uma resposta mais rápida
    setRead((prev) => ({
      ...prev,
      [id]: newRead,
    }));

    // Envia a requisição para a API
    fetch(`http://miraup.test/json/api/v1/notifications`, {
      method: 'PUT',
      headers: {
        'Content-type': 'application/json',
        Authorization: 'Bearer ' + token,
      },
      body: JSON.stringify({
        id,
        change_read: newRead,
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

        // Atualiza o estado de leitura com a resposta da API
        if (json.success) {
          setRead((prev) => ({
            ...prev,
            [id]: json.data.read, // Usa o valor retornado pela API
          }));
        }
      })
      .catch((error) => {
        console.error('Erro:', error);

        // Reverte o estado local em caso de erro
        setRead((prev) => ({
          ...prev,
          [id]: currentRead,
        }));
      });
  };

  //console.log(notifications);

  return (
    <>
      <h2>NOTIFICATIONS GET</h2>
      <br />
      <Row>
        {notifications &&
          notifications.length > 0 &&
          notifications.map(
            ({
              id,
              title,
              content,
              category,
              message,
              post_id,
              author,
              marker,
              url_notification,
              url_post,
            }) => (
              <Col xs={4} key={id}>
                {id}
                <div
                  style={{ display: 'flex', alignItems: 'center' }}
                  className="mb-3 mt-3"
                >
                  <b>Reader: </b> {reads[id] ? 'Lido' : 'Não Lido'}
                  {'  -  '}
                  <Form.Check
                    type="switch"
                    id={id + '-read'}
                    checked={reads[id] || false} // Usa o estado individual de leitura
                    onChange={() => handleRead(id, reads[id])}
                  />
                </div>
                <p>
                  <b>Title:</b> {title}
                </p>
                <p>
                  <b>Content: </b> {content}
                </p>
                <p>
                  <b>Category:</b> {category && category[0].name}
                </p>
                <p>
                  <b>Message short:</b> {message && message}
                </p>
                <p>
                  <b>ID Post:</b> {post_id && post_id}
                </p>
                <p>
                  <b>ID Autor:</b> {author}
                </p>
                <p>
                  <b>URL Notificação:</b> {url_notification}
                </p>
                <p>
                  <b>URL Post:</b> {url_post}
                </p>
                <p>
                  <b>Marker:</b> {marker}
                </p>
                <hr />
              </Col>
            ),
          )}
      </Row>
    </>
  );
};

export default NotificationGetTEST;
