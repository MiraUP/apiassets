import React, { useState, useEffect } from 'react';

const AssetSearch = () => {
  const [token, setToken] = useState(localStorage.getItem('token') || '');
  const [searchQuery, setSearchQuery] = useState('');
  const [assets, setAssets] = useState([]);
  const [filterOptions, setFilterOptions] = useState({
    authors: [],
    categories: [],
    compatibilities: [],
    developers: [],
    origins: [],
  });
  const [filters, setFilters] = useState({
    author: '',
    category: '',
    compatibility: '',
    developer: '',
    origin: '',
    favorite: false,
  });

  // Busca prévia para preencher as opções de filtro
  useEffect(() => {
    const fetchFilterOptions = async () => {
      try {
        const response = await fetch(
          'http://miraup.test/json/api/v1/taxonomy?taxonomy=compatibility',
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          },
        );

        if (!response.ok) {
          throw new Error('Erro ao buscar opções de filtro');
        }

        const data = await response.json();
        console.log(data);
        if (data && data.length > 0) {
          const authors = [...new Set(data.data.map((asset) => asset.author))];
          const categories = [
            ...new Set(data.data.flatMap((asset) => asset.categories)),
          ];
          const compatibilities = [
            ...new Set(data.data.flatMap((asset) => asset.compatibility)),
          ];
          const developers = [
            ...new Set(data.data.flatMap((asset) => asset.developer)),
          ];
          const origins = [
            ...new Set(data.data.flatMap((asset) => asset.origin)),
          ];

          setFilterOptions({
            authors,
            categories,
            compatibilities,
            developers,
            origins,
          });
        }
      } catch (error) {
        console.error('Erro ao buscar opções de filtro:', error);
      }
    };

    fetchFilterOptions();
  }, []);

  // Busca os ativos com base na query e nos filtros
  const searchAssets = async () => {
    try {
      const params = new URLSearchParams({
        search: searchQuery,
        author: filters.author,
        category: filters.category,
        compatibility: filters.compatibility,
        developer: filters.developer,
        origin: filters.origin,
        favorite: filters.favorite,
      });

      const response = await fetch(
        `http://miraup.test/json/api/v1/asset-search?${params.toString()}`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      );

      if (!response.ok) {
        throw new Error('Erro ao buscar ativos');
      }

      const data = await response.json();

      if (data && data.data) {
        setAssets(data.data);
      }
    } catch (error) {
      console.error('Erro ao buscar ativos:', error);
    }
  };

  // Autocomplete após 3 caracteres
  useEffect(() => {
    if (searchQuery.length >= 3) {
      searchAssets();
    }
  }, [searchQuery]);

  return (
    <div>
      <h1>Pesquisar Ativos</h1>

      {/* Campo de pesquisa */}
      <div>
        <input
          type="text"
          placeholder="Pesquisar ativos..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>

      {/* Filtros */}
      <div>
        <label>Autor:</label>
        <select
          value={filters.author}
          onChange={(e) => setFilters({ ...filters, author: e.target.value })}
        >
          <option value="">Todos</option>
          {filterOptions.authors.map((author, index) => (
            <option key={index} value={author}>
              {author}
            </option>
          ))}
        </select>

        <label>Categoria:</label>
        <select
          value={filters.category}
          onChange={(e) => setFilters({ ...filters, category: e.target.value })}
        >
          <option value="">Todas</option>
          {filterOptions.categories.map((category, index) => (
            <option key={index} value={category}>
              {category}
            </option>
          ))}
        </select>

        <label>Compatibilidade:</label>
        <select
          value={filters.compatibility}
          onChange={(e) =>
            setFilters({ ...filters, compatibility: e.target.value })
          }
        >
          <option value="">Todas</option>
          {filterOptions.compatibilities.map((compatibility, index) => (
            <option key={index} value={compatibility.title}>
              {compatibility.title}
              {console.log(compatibility.title)}
            </option>
          ))}
        </select>

        <label>Desenvolvedor:</label>
        <select
          value={filters.developer}
          onChange={(e) =>
            setFilters({ ...filters, developer: e.target.value })
          }
        >
          <option value="">Todos</option>
          {filterOptions.developers.map((developer, index) => (
            <option key={index} value={developer}>
              {developer}
            </option>
          ))}
        </select>

        <label>Origem:</label>
        <select
          value={filters.origin}
          onChange={(e) => setFilters({ ...filters, origin: e.target.value })}
        >
          <option value="">Todas</option>
          {filterOptions.origins.map((origin, index) => (
            <option key={index} value={origin}>
              {origin}
            </option>
          ))}
        </select>

        <label>Favoritos:</label>
        <input
          type="checkbox"
          checked={filters.favorite}
          onChange={(e) =>
            setFilters({ ...filters, favorite: e.target.checked })
          }
        />
      </div>

      {/* Botão de pesquisa */}
      <button onClick={searchAssets}>Pesquisar</button>

      {/* Lista de ativos */}
      <div>
        <h2>Resultados:</h2>
        {assets.length > 0 ? (
          <ul>
            {assets.map((asset) => (
              <li key={asset.id}>
                <h3>{asset.title}</h3>
                <p>{asset.subtitle}</p>
                <p>Autor: {asset.author}</p>
                <p>Categorias: {asset.categories.join(', ')}</p>
                <p>Tags: {asset.tags.join(', ')}</p>
                <p>Compatibilidade: {asset.compatibility.join(', ')}</p>
                <p>Desenvolvedor: {asset.developer.join(', ')}</p>
                <p>Origem: {asset.origin.join(', ')}</p>
              </li>
            ))}
          </ul>
        ) : (
          <p>Nenhum ativo encontrado.</p>
        )}
      </div>
    </div>
  );
};

export default AssetSearch;
