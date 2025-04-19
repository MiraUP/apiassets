import React from 'react';
import NotificationGetTEST from '../endpoints/NotificationGet_TEST';
import NotificationPostTEST from '../endpoints/NotificationPost_TEST';
import NotificationPutTEST from '../endpoints/NotificationPut_TEST';

const Notifications = () => {
  return (
    <>
      <NotificationGetTEST />
      <br />
      <hr />
      <br />
      <NotificationPostTEST />
      <br />
      <hr />
      <br />
      <NotificationPutTEST />
    </>
  );
};

export default Notifications;
