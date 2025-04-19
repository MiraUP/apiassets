import React from 'react';
import TaxonomysGetTEST from '../endpoints/TaxonomyGet_TEST';
import TaxonomysPostTEST from '../endpoints/TaxonomyPost_TEST';
import TaxonomysPutTEST from '../endpoints/TaxonomyPut_TEST';
import TaxonomysDeleteTEST from '../endpoints/TaxonomyDelete_TEST';
import TaxonomySearchTEST from '../endpoints/TaxonomySearch_TEST';

const APIPageTaxonomy = () => {
  return (
    <>
      <TaxonomysGetTEST />
      <br />
      <hr />
      <br />
      <TaxonomySearchTEST />
      <br />
      <hr />
      <br />
      <TaxonomysPostTEST />
      <br />
      <hr />
      <br />
      <TaxonomysPutTEST />
      <br />
      <hr />
      <br />
      <TaxonomysDeleteTEST />
    </>
  );
};

export default APIPageTaxonomy;
