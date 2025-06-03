import React, { useState, useEffect } from 'react';
import {
  TextField,
  Button,
  Select,
  MenuItem,
  Grid,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TablePagination,
  CircularProgress,
  Box,
  Typography,
} from '@mui/material';

const NotificationSearch = () => {
  const [token, setToken] = React.useState(localStorage.getItem('token') || '');

  // Estados para gerenciar a busca
  const [searchParams, setSearchParams] = useState({
    title: '',
    marker: '',
    user_id: '',
    status: '',
    page: 1,
    per_page: 10,
    orderby: 'created_at',
    order: 'DESC',
  });

  const [results, setResults] = useState([]);
  const [pagination, setPagination] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  // Função para realizar a busca
  const handleSearch = async () => {
    setIsLoading(true);
    setError(null);

    try {
      // Construir query string
      const params = new URLSearchParams();
      Object.entries(searchParams).forEach(([key, value]) => {
        if (value) params.append(key, value);
      });

      const url = `http://miraup.test/json/api/v1/notifications-search?${params.toString()}`;

      // Fazer a requisição
      const response = await fetch(url, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error(`Erro na busca: ${response.status}`);
      }

      const data = await response.json();

      if (!response.ok) {
        const errorData = await response.json().catch(() => null);
        throw new Error(
          errorData?.message || `Erro na busca: ${response.status}`,
        );
      }
      if (data.success) {
        setResults(data.data.notifications);
        console.log('Resultados:', data.data.notifications);
        setPagination(data.data.pagination);
      } else {
        throw new Error(data.message || 'Erro desconhecido na resposta');
      }
    } catch (err) {
      console.log('Resultados:', err.message);
      setError(err.message);
      console.error('Erro na busca:', err);
    } finally {
      setIsLoading(false);
    }
  };

  // Manipuladores de paginação
  const handlePageChange = (event, newPage) => {
    setSearchParams({ ...searchParams, page: newPage + 1 });
  };

  const handlePerPageChange = (e) => {
    setSearchParams({
      ...searchParams,
      per_page: e.target.value,
      page: 1,
    });
  };

  // Executar busca ao mudar página ou quantidade por página
  useEffect(() => {
    handleSearch();
  }, [searchParams.page, searchParams.per_page]);

  return (
    <Paper sx={{ p: 3, mb: 4 }}>
      <Typography variant="h5" gutterBottom sx={{ mb: 3 }}>
        Busca de Notificações
      </Typography>

      <Grid container spacing={2} sx={{ mb: 3 }}>
        <Grid item xs={12} md={3}>
          <TextField
            label="Título"
            fullWidth
            value={searchParams.title}
            onChange={(e) =>
              setSearchParams({ ...searchParams, title: e.target.value })
            }
            variant="outlined"
          />
        </Grid>
        <Grid item xs={12} md={3}>
          <TextField
            label="Categoria"
            fullWidth
            value={searchParams.marker}
            onChange={(e) =>
              setSearchParams({ ...searchParams, marker: e.target.value })
            }
            variant="outlined"
          />
        </Grid>
        <Grid item xs={12} md={2}>
          <TextField
            label="ID do Usuário"
            type="number"
            fullWidth
            value={searchParams.user_id}
            onChange={(e) =>
              setSearchParams({ ...searchParams, user_id: e.target.value })
            }
            variant="outlined"
          />
        </Grid>
        <Grid item xs={12} md={2}>
          <TextField
            label="Status"
            select
            fullWidth
            value={searchParams.status}
            onChange={(e) =>
              setSearchParams({ ...searchParams, status: e.target.value })
            }
            variant="outlined"
          >
            <MenuItem value="">Todos</MenuItem>
            <MenuItem value="read">Lidas</MenuItem>
            <MenuItem value="unread">Não lidas</MenuItem>
            <MenuItem value="archived">Arquivadas</MenuItem>
          </TextField>
        </Grid>
        <Grid
          item
          xs={12}
          md={2}
          sx={{ display: 'flex', alignItems: 'flex-end' }}
        >
          <Button
            variant="contained"
            onClick={handleSearch}
            fullWidth
            disabled={isLoading}
            sx={{ height: '56px' }}
          >
            {isLoading ? <CircularProgress size={24} /> : 'Buscar'}
          </Button>
        </Grid>
      </Grid>

      {error && (
        <Box
          sx={{
            bgcolor: 'error.light',
            color: 'error.contrastText',
            p: 2,
            borderRadius: 1,
            mb: 2,
          }}
        >
          {error}
        </Box>
      )}

      {isLoading && results.length === 0 ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}>
          <CircularProgress />
        </Box>
      ) : results.length > 0 ? (
        <>
          <TableContainer>
            <Table>
              <TableHead>
                <TableRow sx={{ bgcolor: 'primary.main' }}>
                  <TableCell sx={{ color: 'white' }}>ID</TableCell>
                  <TableCell sx={{ color: 'white' }}>Título</TableCell>
                  <TableCell sx={{ color: 'white' }}>Marker</TableCell>
                  <TableCell sx={{ color: 'white' }}>Destinatário</TableCell>
                  <TableCell sx={{ color: 'white' }}>Data</TableCell>
                  <TableCell sx={{ color: 'white' }}>Status</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {results.map((notification) => (
                  <TableRow key={notification.id} hover>
                    <TableCell>{notification.id}</TableCell>
                    <TableCell>{notification.title}</TableCell>
                    <TableCell>{notification.marker}</TableCell>
                    <TableCell>{notification.user_id || 'Todos'}</TableCell>
                    <TableCell>
                      {new Date(notification.created_at).toLocaleDateString(
                        'pt-BR',
                      )}
                    </TableCell>
                    <TableCell>
                      <Box
                        sx={{
                          display: 'inline-block',
                          px: 1,
                          borderRadius: 1,
                          bgcolor:
                            notification.status === 'unread'
                              ? 'warning.light'
                              : notification.status === 'read'
                              ? 'success.light'
                              : 'secondary.light',
                          color:
                            notification.status === 'unread'
                              ? 'warning.contrastText'
                              : notification.status === 'read'
                              ? 'success.contrastText'
                              : 'secondary.contrastText',
                        }}
                      >
                        {notification.status === 'unread'
                          ? 'Não lida'
                          : notification.status === 'read'
                          ? 'Lida'
                          : 'Arquivada'}
                      </Box>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

          <TablePagination
            component="div"
            count={pagination.total || 0}
            page={pagination.current_page ? pagination.current_page - 1 : 0}
            onPageChange={handlePageChange}
            rowsPerPage={searchParams.per_page}
            onRowsPerPageChange={handlePerPageChange}
            rowsPerPageOptions={[5, 10, 20, 50]}
            sx={{ mt: 2 }}
          />
        </>
      ) : (
        !isLoading && (
          <Box
            sx={{
              textAlign: 'center',
              p: 4,
              border: '1px dashed #ccc',
              borderRadius: 1,
              mt: 2,
            }}
          >
            <Typography variant="body1">
              Nenhuma notificação encontrada. Tente alterar seus filtros de
              busca.
            </Typography>
          </Box>
        )
      )}
    </Paper>
  );
};

export default NotificationSearch;
