import React from 'react';
import UserPostTEST from '../endpoints/UserPost_TEST';
import TokenPostTEST from '../endpoints/TokenUser_TEST';
import UserGetTEST from '../endpoints/UserGet_TEST';
import UserPutTEST from '../endpoints/UserPut_TEST';
import UserDeleteTEST from '../endpoints/UserDelete_TEST';
import UserSearchTEST from '../endpoints/UserSearch_TEST';

const APIPageUser = () => {
  return (
    <>
      <TokenPostTEST />
      <br />
      <hr />
      <br />
      <UserSearchTEST />
      <br />
      <hr />
      <br />
      <UserPostTEST />
      <br />
      <hr />
      <br />
      <UserGetTEST />
      <br />
      <hr />
      <br />
      <UserPutTEST />
      <br />
      <hr />
      <br />
      <UserDeleteTEST />
    </>
  );
};

export default APIPageUser;
