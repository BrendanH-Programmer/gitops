provider "azurerm" {
  client_id       = var.client_id
  client_secret   = var.client_secret
  tenant_id       = var.tenant_id
  subscription_id = var.subscription_id
  features {}
}

resource "azurerm_resource_group" "example" {
  name     = "example-resources"
  location = "UK SOUTH"
}

output "resource_group_name" {
  value = azurerm_resource_group.example.name
}