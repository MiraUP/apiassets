import React from 'react';
import AssetsSearchTEST from '../endpoints/AssetsSearch_TEST';
import AssetsPostTEST from '../endpoints/AssetsPost_TEST';
import AssetsGetTEST from '../endpoints/AssetsGet_TEST';
import AssetsPutTEST from '../endpoints/AssetsPut_TEST';
import AssetsDeleteTEST from '../endpoints/AssetsDelete_TEST';

const APIPageAssets = () => {
  return (
    <>
      <AssetsSearchTEST />
      <br />
      <hr />
      <br />
      <AssetsPostTEST />
      <br />
      <hr />
      <br />
      <AssetsGetTEST />
      <br />
      <hr />
      <br />
      <AssetsPutTEST />
      <br />
      <hr />
      <br />
      <AssetsDeleteTEST />
    </>
  );
};

export default APIPageAssets;
