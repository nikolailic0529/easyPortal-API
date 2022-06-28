DELETE
FROM audits
where `action` like 'model.updated' AND JSON_KEYS(`context`, '$.properties') = JSON_ARRAY("synced_at", "updated_at");
