import React from 'react';
import MediaGetTEST from '../endpoints/MediaGet_TEST';
import MediaPutTEST from '../endpoints/MediaPut_TEST';
import MediaSearchTEST from '../endpoints/MediaSearch_TEST';
import MediaPostTEST from '../endpoints/MediaPost_TEST';

const APIPageMedia = () => {
  return (
    <>
      <MediaGetTEST />
      <br />
      <hr />
      <br />
      <MediaSearchTEST />
      <br />
      <hr />
      <br />
      <MediaPostTEST />
      <br />
      <hr />
      <br />
      <MediaPutTEST />
    </>
  );
};

export default APIPageMedia;
