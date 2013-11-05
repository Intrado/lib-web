<?

//
// Requires Topic

class TopicDataManager {

	function TopicDataManager() {

	}

	public function rootOrganizationId() {
		return QuickQuery("
SELECT id
FROM organization
WHERE (SELECT 1 FROM setting WHERE name LIKE '_dbtaiversion')
AND parentorganizationid IS null AND NOT deleted");
	}

	public function topicsInfo($start,$limit) {
		$topics = QuickQueryMultiRow("
SELECT SQL_CALC_FOUND_ROWS tai_topic.id, tai_topic.name FROM tai_topic
JOIN tai_organizationtopic
ON tai_organizationtopic.topicid = tai_topic.id
WHERE tai_organizationtopic.organizationid = ?
LIMIT $start, $limit", true, false, array($this->rootOrganizationId()));

		$total = QuickQuery("select FOUND_ROWS()");

		return array("data" => $topics, "total" => $total);
	}

	public function deleteTopic($topicid) {
		return QuickUpdate("delete from tai_organizationtopic
where topicid = ?
and organizationid = ?", false, array($topicid, $this->rootOrganizationId()));
	}

	public function updateTopicName($topicid, $topicname) {
		QuickUpdate("update tai_topic set name = ? where id = ?", false, array($topicname, $topicid));
	}

	public function createTopic($topicBuilder,$topicname) {
		$topic = new Topic();
		$topic->name = $topicname;
		$topic->create();
		QuickUpdate("insert into tai_organizationtopic (organizationid, topicid) values (?, ?)",
								false,
								array($this->rootOrganizationId(), $topic->id));
	}

}

?>