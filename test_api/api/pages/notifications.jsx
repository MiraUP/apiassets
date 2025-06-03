import React from 'react';
import NotificationGetTEST from '../endpoints/NotificationGet_TEST';
import NotificationPostTEST from '../endpoints/NotificationPost_TEST';
import NotificationPutTEST from '../endpoints/NotificationPut_TEST';
import NotificationSearch from '../endpoints/NotificationSearch_TEST';

const Notifications = () => {
  return (
    <>
      <NotificationSearch />
      <br />
      <hr />
      <br />
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
