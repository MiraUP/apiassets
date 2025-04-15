import React from 'react';
import PasswordLostTEST from '../endpoints/PasswordLost_TEST';
import PasswordResetTEST from '../endpoints/PasswordReset_TEST';

const APIPagePassword = () => {
  return (
    <>
      <PasswordLostTEST />
      <br />
      <hr />
      <br />
      <PasswordResetTEST />
    </>
  );
};

export default APIPagePassword;
