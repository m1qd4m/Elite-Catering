<?php
// includes/header.php — Shared Dashboard Header
// Requires: $pageTitle, session already started
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — Élite Catering</title>
  <link rel="stylesheet" href="<?= $baseUrl ?? '../' ?>assets/css/variables.css">
  <link rel="stylesheet" href="<?= $baseUrl ?? '../' ?>assets/css/base.css">
  <link rel="stylesheet" href="<?= $baseUrl ?? '../' ?>assets/css/components.css">
  <link rel="stylesheet" href="<?= $baseUrl ?? '../' ?>assets/css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
