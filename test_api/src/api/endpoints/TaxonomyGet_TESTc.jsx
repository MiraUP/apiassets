import React, { useState, useEffect } from 'react';
import { Form, Table, Container, Alert } from 'react-bootstrap';

const TaxonomyList = () => {
  const [taxonomies, setTaxonomies] = useState([]);
  const [selectedTaxonomy, setSelectedTaxonomy] = useState('category');
  const [message, setMessage] = useState('');
  const [isError, setIsError] = useState(false);

  // Busca o token no localStorage
  const token = localStorage.getItem('token');

  // Lista de taxonomias disponíveis
  const taxonomyOptions = [
    { value: '', label: 'Selecione uma taxonomia' },
    { value: 'category', label: 'Categoria' },
    { value: 'post_tag', label: 'Tag' },
    { value: 'developer', label: 'Desenvolvedor' },
    { value: 'origin', label: 'Origem' },
    { value: 'compatibility', label: 'Compatibilidade' },
    { value: 'icon_category', label: 'Categoria de Ícone' },
    { value: 'icon_style', label: 'Estilo de Ícone' },
    { value: 'icon_tag', label: 'Tag de Ícone' },
  ];

  // Função para buscar as taxonomias
  const fetchTaxonomies = async (taxonomy) => {
    try {
      const url = new URL(
        `http://miraup.test/json/api/v1/taxonomy/?taxonomy=${selectedTaxonomy}`,
      );

      const response = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao buscar as taxonomias.');
      }

      const data = await response.json();
      setTaxonomies(data);
      setIsError(false);
      setMessage('');
    } catch (error) {
      setMessage(error.message || 'Erro ao buscar as taxonomias.');
      setIsError(true);
      setTaxonomies([]);
    }
  };

  // Atualiza a lista de taxonomias quando a seleção muda
  useEffect(() => {
    if (selectedTaxonomy) {
      selectedTaxonomy;
    } else {
      setTaxonomies([]); // Limpa a lista se nenhuma taxonomia for selecionada
    }
  }, [selectedTaxonomy]);

  return (
    <Container className="mt-5">
      <h2>Lista de Taxonomias</h2>
      {message && (
        <Alert variant={isError ? 'danger' : 'success'}>{message}</Alert>
      )}

      <Form.Group className="mb-3">
        <Form.Label>Selecione a Taxonomia</Form.Label>
        <Form.Select
          value={selectedTaxonomy}
          onChange={(e) => setSelectedTaxonomy(e.target.value)}
        >
          {taxonomyOptions.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </Form.Select>
      </Form.Group>

      {taxonomies.length > 0 && (
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
            {taxonomies.map((taxonomy) => (
              <tr key={taxonomy.term_id}>
                <td>{taxonomy.term_id}</td>
                <td>{taxonomy.name}</td>
                <td>{taxonomy.slug}</td>
                <td>{taxonomy.description}</td>
              </tr>
            ))}
          </tbody>
        </Table>
      )}
    </Container>
  );
};

export default TaxonomyList;
