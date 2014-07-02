<?

//
// Requires Topic
// Requires OrganizationTopic

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
order by tai_topic.name
LIMIT $start, $limit", true, false, array($this->rootOrganizationId()));

		$total = QuickQuery("select FOUND_ROWS()");

		return array("data" => $topics, "total" => $total);
	}

	public function deleteTopic($topicid) {
		return QuickUpdate("delete from tai_organizationtopic
where topicid = ?
and organizationid = ?", false, array($topicid, $this->rootOrganizationId()));
	}

	public function updateTopicName($topic, $topicname) {
		$topic->name = $topicname;
		$topic->update();
	}

	public function createTopic($topic,$topicname) {
		$topic->name = $topicname;
		$topic->create();
		$organizationtopic = $this->organizationTopicFor($topic->id);
		$organizationtopic->create();
	}

	private function organizationTopicFor($topicid) {
		$organizationtopic = new OrganizationTopic();
		$organizationtopic->organizationid = $this->rootOrganizationId();
		$organizationtopic->topicid = $topicid;
		return $organizationtopic;
	}


}

?>