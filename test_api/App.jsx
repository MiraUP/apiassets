import React from 'react';
import './main.min.css';
import { BrowserRouter, Route, Routes, Link } from 'react-router-dom';
import { Nav, Navbar, NavDropdown, Container } from 'react-bootstrap';
import APIPageUser from './api/pages/user';
import APIPagePassword from './api/pages/password';
import APIPageAsset from './api/pages/assets';
import APIPageTaxonomy from './api/pages/taxonomy';
import APIPageMedia from './api/pages/media';
import APIPageNotifications from './api/pages/notifications';
import APIPageComments from './api/pages/comments';

function App() {
  return (
    <>
      <BrowserRouter>
        <Navbar
          expand="lg"
          className="bg-body-tertiary position-fixed w-100"
          style={{ zIndex: '1000' }}
        >
          <Container fluid>
            <Navbar.Brand href="#home">CRUD API</Navbar.Brand>
            <Navbar.Toggle aria-controls="basic-navbar-nav" />
            <Navbar.Collapse id="basic-navbar-nav">
              <Nav className="me-auto">
                <Link className="nav-link" to="/user">
                  User
                </Link>
                <Link className="nav-link" to="/password">
                  Password
                </Link>
                <Link className="nav-link" to="/assets">
                  Assets
                </Link>
                <Link className="nav-link" to="/comments">
                  Comments
                </Link>
                <Link className="nav-link" to="/taxonomy">
                  Taxonomys
                </Link>
                <Link className="nav-link" to="/media">
                  Medias
                </Link>
                <Link className="nav-link" to="/notification">
                  Notifications
                </Link>
              </Nav>
            </Navbar.Collapse>
          </Container>
        </Navbar>
        <Container fluid style={{ paddingTop: '80px', paddingBottom: '40px' }}>
          <Routes>
            <Route path="/*" element={<APIPageUser />} />
            <Route path="/user/*" element={<APIPageUser />} />
            <Route path="/password/*" element={<APIPagePassword />} />
            <Route path="/reset-password/*" element={<APIPagePassword />} />
            <Route path="/assets/*" element={<APIPageAsset />} />
            <Route path="/comments/*" element={<APIPageComments />} />
            <Route path="/taxonomy/*" element={<APIPageTaxonomy />} />
            <Route path="/media/*" element={<APIPageMedia />} />
            <Route path="/notification/*" element={<APIPageNotifications />} />
          </Routes>
        </Container>
      </BrowserRouter>
    </>
  );
}

export default App;
