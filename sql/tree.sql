CREATE DATABASE IF NOT EXISTS `ideotree` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `ideotree`;

CREATE TABLE `directories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `privilages` binary(4) NOT NULL,
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `directories` (`id`, `name`, `parent_id`, `privilages`, `create_time`) VALUES
(1, '[root]', NULL, 0x00000000, '2018-05-30 08:41:33');

ALTER TABLE `directories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `directories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;