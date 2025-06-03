import React from 'react';
import StatisticsGetTEST from '../endpoints/StatisticsGet_TEST';
import TaxonomysPostTEST from '../endpoints/TaxonomyPost_TEST';
import TaxonomysPutTEST from '../endpoints/TaxonomyPut_TEST';
import TaxonomysDeleteTEST from '../endpoints/TaxonomyDelete_TEST';
import TaxonomySearchTEST from '../endpoints/TaxonomySearch_TEST';

const APIPageTaxonomy = () => {
  return (
    <>
      <StatisticsGetTEST />
      <br />
      <hr />
      <br />
    </>
  );
};

export default APIPageTaxonomy;
