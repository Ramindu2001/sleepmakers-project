<!-- Inventory Selection Modal -->
<div class="modal fade" id="ShortcutListModal" tabindex="-1" aria-labelledby="ShortcutListModalLabel" aria-hidden="true" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inventoryModalLabel">Shortcut Keys</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Keys</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * FROM shortcutkeys";
                            $dbObj = new DBTransactions();
                            $shortcutkeys = $dbObj->getData($sql);
                            foreach ($shortcutkeys as $row) 
                            {
                                ?>
                                <tr>
                                    <td class="text-center" style="padding:5px !important;"><b><?=$row["keys"]?></b></td>
                                    <td style="padding:5px !important;"><?=$row["description"]?></td>
                                </tr>
                                <?php
                            }
                            ?>
                                                        
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
