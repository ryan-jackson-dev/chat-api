-- Enable Foreign Key support for SQLite
PRAGMA foreign_keys = ON;

-- 1. Users Table: Stores the global list of users
--    a) There is a host of other information we might also need such as
--       permission information (requires further thought).
CREATE TABLE IF NOT EXISTS users (
    id TEXT NOT NULL PRIMARY KEY CHECK (length(id) = 36), -- UUID4
    name TEXT NOT NULL UNIQUE CHECK (length(name) BETWEEN 1 AND 128),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- See UserRepository::findAll.
CREATE INDEX IF NOT EXISTS idx_users_created_at_id
ON users(created_at, id);


-- 2. Groups Table: Stores the chat groups
--    a) One might consider a host of other metadata such as a description.
--    b) It is possible that one might want to leave a group that they created.
--       If so, then we need to account for that.
CREATE TABLE IF NOT EXISTS groups (
    id TEXT NOT NULL PRIMARY KEY CHECK (length(id) = 36), -- UUID7 (same length as UUID4)
    name TEXT NOT NULL UNIQUE CHECK (length(name) BETWEEN 1 AND 128),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by TEXT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- See GroupRepository::findAll
CREATE INDEX IF NOT EXISTS idx_groups_created_at_id 
ON groups(created_at, id);


-- 3. Group Memberships Table: A user can be in many groups
--    a) The cascading delete makes sense here, but see the other case below.
CREATE TABLE IF NOT EXISTS group_memberships (
    group_id TEXT NOT NULL, -- See groups.id
    user_id TEXT NOT NULL,  -- See users.id
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


-- 4. Messages Table: Tied to a group and an author (user)
--    a) I am not sure about the cascading delete. It is possible that one might
--       want to retain messages. A question for the Product Owner. We may want
--       to use "SET NULL" instead.
--    b) We might want a different limit on message (content) size.
--    c) We might also want to allow users to send messages between users.
--    d) Need to reflect on how to represent it, but we might also want a
--       "read_at" timestamp for each message per user_id. This would need a
--       separate table.
--    e) If the message is subject to delay, the created_at timestamp will only
--       reflect the time it was persisted, not when it was sent. We might add
--       a "sent_at" timestamp but there is at least one security consideration.
--    f) The resolution of this timestamp is such that ordering needs to be done
--       in conjunction with the monotonically increasing id (primary key). We
--       could consider using UUID7 for the PK like we do for the groups table.
CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id TEXT NOT NULL, -- See groups.id
    user_id TEXT NOT NULL,  -- See users.id
    content TEXT NOT NULL CHECK (length(content) BETWEEN 1 AND 1024),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- To get all messages by user.
CREATE INDEX IF NOT EXISTS idx_messages_user_id
ON messages(user_id);

-- See MessageRepository::findByGroup.
CREATE INDEX IF NOT EXISTS idx_messages_pagination 
ON messages(group_id, created_at, id);
