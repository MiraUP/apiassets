import React from 'react';
import NotificationGetTEST from '../endpoints/NotificationGet_TEST';
import NotificationPostTEST from '../endpoints/NotificationPost_TEST';
import NotificationPutTEST from '../endpoints/NotificationPut_TEST';
import NotificationErrorPost from '../endpoints/NotificationErrorPost_TEST';
import NotificationDeleteTEST from '../endpoints/NotificationDelete_TEST';

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
      <br />
      <hr />
      <br />
      <NotificationErrorPost />
      <br />
      <hr />
      <br />
      <NotificationDeleteTEST />
    </>
  );
};

export default Notifications;
