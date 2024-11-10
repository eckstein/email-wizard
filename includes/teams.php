<?php
class wizardTeam
{
    private $id;
    private $name;
    private $created_at;
    private $wpdb;

    public function __construct($args = [])
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        if (isset($args['id'])) {
            $this->id = $args['id'];
            $this->load();
        } else {
            $this->name = $args['name'] ?? '';
            $this->created_at = current_time('mysql');
        }
    }

    public function load()
    {
        $team = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}teams WHERE id = %d",
                $this->id
            ),
            ARRAY_A
        );

        if ($team) {
            $this->name = $team['name'];
            $this->created_at = $team['created_at'];
            return true;
        }
        return false;
    }

    public function save()
    {
        if ($this->id) {
            return $this->wpdb->update(
                "{$this->wpdb->prefix}teams",
                [
                    'name' => $this->name
                ],
                ['id' => $this->id]
            );
        } else {
            $result = $this->wpdb->insert(
                "{$this->wpdb->prefix}teams",
                [
                    'name' => $this->name,
                    'created_at' => $this->created_at
                ]
            );
            if ($result) {
                $this->id = $this->wpdb->insert_id;
                return true;
            }
        }
        return false;
    }

    public function addMember($user_id)
    {
        return $this->wpdb->insert(
            "{$this->wpdb->prefix}user_team_associations",
            [
                'user_id' => $user_id,
                'team_id' => $this->id
            ]
        );
    }

    public function removeMember($user_id)
    {
        return $this->wpdb->delete(
            "{$this->wpdb->prefix}user_team_associations",
            [
                'user_id' => $user_id,
                'team_id' => $this->id
            ]
        );
    }

    public function getMembers()
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT u.* FROM {$this->wpdb->users} u 
                JOIN {$this->wpdb->prefix}user_team_associations uta 
                ON u.ID = uta.user_id 
                WHERE uta.team_id = %d",
                $this->id
            )
        );
    }

    public function delete()
    {
        if ($this->id) {
            return $this->wpdb->delete(
                "{$this->wpdb->prefix}teams",
                ['id' => $this->id]
            );
        }
        return false;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    // Setter
    public function setName($name)
    {
        $this->name = $name;
    }
}
