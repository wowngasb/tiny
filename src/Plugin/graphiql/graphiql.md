# 测试GraphQL

## 测试查询

查询语句

```
query TestQuery($num: Int!, $page: Int!, $room_id: ID!, $user_id: ID!) {
  room(room_id: $room_id) {
    room_id
    room_title
    room_status
    currentUser {
      ...CurrentUserF
    }
    topicUser(num: $num, page: $page) {
      userList {
        ...CurrentUserF
      }
      pageInfo {
        ...PageInfoF
      }
    }
  }
  user(room_id: $room_id, user_id: $user_id) {
    ...BasicUserF
  }
}

fragment CurrentUserF on CurrentUser {
  user {
    ...BasicUserF
  }
  user_agent
  client_id
}

fragment BasicUserF on BasicUser {
  user_id
  user_type
  nick
  avatar
}

fragment PageInfoF on PageInfo {
  num
  total
  page
  hasNextPage
  hasPreviousPage
}
```

Query Variables

```
{
  "room_id": 101,
  "user_id": 1001,
  "num": 5,
  "page": 1
}

```