DELIMITER $$
DROP TRIGGER IF EXISTS asterisk.cdr_insert$$
CREATE TRIGGER cdr_insert BEFORE INSERT ON cdr
FOR EACH ROW BEGIN

declare _matches INTEGER;
declare _id INTEGER;
declare _type VARCHAR(20);
declare _cost FLOAT;
declare _min INTEGER;
declare _inc INTEGER;

DECLARE EXIT HANDLER FOR NOT FOUND BEGIN
    SET NEW.route=0;
    SET NEW.type='unknown';
    SET NEW.cost=0;
END;

SELECT id,cost,type,min,increment INTO _id, _cost, _type, _min, _inc
    FROM cdr_routes
    WHERE
    (channel = '' OR NEW.channel LIKE channel) AND
    (dcontext = '' OR NEW.dcontext LIKE dcontext) AND
    (dstchannel = '' OR NEW.dstchannel LIKE dstchannel) AND
    (src = '' OR NEW.src LIKE src) AND
    (dst = '' OR NEW.dst LIKE dst)
    ORDER BY priority
    LIMIT 1;

SET _inc = GREATEST(_inc, 1);

IF (NEW.billsec > 0) THEN
    SET NEW.billsec = GREATEST(_min, ceil(NEW.billsec / _inc) * _inc);
END IF;

SET NEW.type = _type;
SET NEW.route = _id;
SET NEW.cost = (NEW.billsec/60) * _cost;

END;
$$

DELIMITER ;
